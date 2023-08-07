<?php

namespace TromsFylkestrafikk\Netex\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;
use TromsFylkestrafikk\Netex\Console\NeTEx\NetexDatabase;
use TromsFylkestrafikk\Netex\Console\NeTEx\NetexFileParser;
use TromsFylkestrafikk\Netex\Console\Traits\LogAndPrint;
use TromsFylkestrafikk\Netex\Models\Import;
use TromsFylkestrafikk\Netex\Services\StopsActivator;
use TromsFylkestrafikk\Netex\Services\RouteSet;

class RoutedataImport extends Command
{
    use LogAndPrint;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:routedata-import
                            {path : Path to route set directory, relative to netex disk root}
                            {main : Filename of shared NeTEx XML data}
                            {--f|force : Force update, even if not modified}
                            {--s|no-sync-stops : Don\'t sync active stop places found in set}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Import route data from XML files in NeTEx format.";

    /**
     * Progress bar
     *
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progressBar = null;

    /**
     * @var \TromsFylkestrafikk\Netex\Services\RouteSet
     */
    protected $routeSet;

    /**
     * @var \TromsFylkestrafikk\Netex\Models\Import;
     */
    protected $import;

    /**
     * @var \TromsFylkestrafikk\Netex\Console\NeTEx\NetexFileParser
     */
    protected $parser;

    /**
     * @var \TromsFylkestrafikk\Netex\Services\StopsActivator
     */
    protected $stopsActivator;

    /**
     * Number of line XML files processed.
     *
     * @var integer
     */
    protected $linesProcessed = 0;

    /**
     * @var bool
     */
    protected $sharedIsProcessed = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(StopsActivator $stopsActivator): int
    {
        $this->stopsActivator = $stopsActivator;
        $this->lpInfo('Importing NeTEx route data files...');

        // Check files to be imported.
        $netexDir = trim($this->argument('path'), '/');
        $mainXmlFile = $this->argument('main');
        $this->routeSet = new RouteSet($netexDir, $mainXmlFile);
        if (!$this->option('force') && !$this->routeSet->isModified()) {
            $this->lpInfo("Route set not modified: Not importing. Use --force to override");
            return self::SUCCESS;
        }
        $this->setupProgressBar();
        $this->import = Import::create([
            'path' => $netexDir,
            'md5' => $this->routeSet->getMd5(),
            'size' => $this->routeSet->getSize(),
            'files' => count($this->routeSet->getFiles()),
            'import_status' => 'importing',
            'message' => 'Importing core netex data.',
        ]);

        try {
            $this->processFiles();
            $this->finalizeImport();
            $this->maybeSyncStops();
        } catch (Exception $except) {
            $this->import->fill(['import_status' => 'error', 'message' => $except->getMessage()])->save();
            throw $except;
        }

        $this->import->fill(['import_status' => 'imported', 'message' => null])->save();
        $this->lpInfo(sprintf(
            "Route data import complete: Period: %s – %s. Version: %s. Lines processed: %d",
            $this->parser->availableFrom,
            $this->parser->availableTo,
            $this->parser->version,
            $this->linesProcessed
        ));
        return self::SUCCESS;
    }

    protected function setupProgressBar(): void
    {
        // Progress bar setup. Initial settings used for "Parse main file".
        ProgressBar::setFormatDefinition('custom', '%percent%% [%bar%]  %elapsed% - %message%');
        $this->progressBar = $this->output->createProgressBar();
        $this->progressBar->setFormat('custom');
    }

    /**
     * Process and import entire route set.
     *
     * @return void
     */
    protected function processFiles(): void
    {
        $database = new NetexDatabase();
        $this->parser = new NetexFileParser($this->routeSet->getSharedFilePath());

        // Parse all XML files in route set.
        $files = $this->routeSet->getFiles();
        $this->progressBar->setMaxSteps(count($files));
        $this->progressBar->start();

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $this->progressBar->setMessage("Processing $filename");
            $this->progressBar->advance();
            $database->resetStats();
            if ($filename === $this->routeSet->getSharedFile()) {
                $this->processSharedFile($database);
                $this->sharedIsProcessed = true;
            } else {
                $this->linesProcessed++;
                $this->parser->parseLineXmlFile($filePath);
                // Update database with data from NeTEx line file.
                $database->writeRoutes($this->parser->routes);
                $database->writeJourneyPatterns($this->parser->journeyPatterns);
                $database->writeVehicleJourneys($this->parser->vehicleJourneys);
                $database->writeNoticeAssignments($this->parser->noticeAssignments);
            }
            Log::debug(sprintf("NeTEx: file '%s' stats: %s", $filename, $database->statsToStr()));
        }
        $database->writeLines($this->parser->lines);
        $this->progressBar->finish();
        $this->newLine();
    }

    protected function processSharedFile(NetexDatabase $database): void
    {
        $this->parser->parseMainXmlFile();
        $this->parser->generateCalendar();
        $database->writeCalendar($this->parser->calendar);
        $database->writeOperators($this->parser->operators);
        $database->writeGroupOfLines($this->parser->groupOfLines);
        $database->writeScheduledStopPoints($this->parser->scheduledStopPoints);
        $database->writeDestinationDisplays($this->parser->destinationDisplays);
        $database->writeStopAssignments($this->parser->stopAssignments);
        $database->writeServiceLinks($this->parser->serviceLinks);
        $database->writeVehicleSchedules($this->parser->vehicleSchedules);
        $database->writeNotices($this->parser->notices);
        // Free up menory.
        $this->parser->reset();
    }

    /**
     * Update 'active' stops, seen in this data set.
     *
     * @return void
     */
    protected function maybeSyncStops(): void
    {
        if (!$this->option('no-sync-stops')) {
            $this->import->message = 'Activating seen stops in route set.';
            $this->import->save();
            $this->stopsActivator->update();
        }
    }

    /**
     * Finish up route import.
     */
    protected function finalizeImport(): void
    {
        if (!$this->linesProcessed || !$this->parser->availableFrom || !$this->parser->availableTo) {
            throw new Exception('Imported route set is missing critical data.');
        }
        $this->import->fill([
            'available_from' => new Carbon($this->parser->availableFrom),
            'available_to' =>  new Carbon($this->parser->availableTo),
            'version' => $this->parser->version,
        ])->save();
        $this->import->save();
    }
}
