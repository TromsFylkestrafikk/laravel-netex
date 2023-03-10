<?php

namespace TromsFylkestrafikk\Netex\Console;

use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use TromsFylkestrafikk\Netex\Console\NeTEx\NetexDatabase;
use TromsFylkestrafikk\Netex\Console\NeTEx\NetexFileParser;
use TromsFylkestrafikk\Netex\Console\Traits\LogAndPrint;
use TromsFylkestrafikk\Netex\Models\Import;
use TromsFylkestrafikk\Netex\Services\StopsActivator;
use TromsFylkestrafikk\Netex\Services\RouteSet;

class ImportRouteData extends Command
{
    use LogAndPrint;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:import-routedata
                            {path : Path to route set directory, relative to netex disk root}
                            {main : Filename of shared NeTEx XML data}
                            {--f|force : Force update, even if not modified}
                            {--s|no-sync-stops : Don\'t sync active stop places found in set}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Import route data from XML files in NeTEx format.\n";

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
     * @var \TromsFylkestrafikk\Netex\Services\StopsActivator
     */
    protected $stopsActivator;

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
    public function handle(StopsActivator $stopsActivator)
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
            'import_status' => 'importing',
            'message' => 'Importing core netex data.',
        ]);
        try {
            $this->processFiles();
            $this->maybeSyncStops();
        } catch (Exception $except) {
            $this->import->fill(['import_status' => 'error', 'message' => $except->getMessage()])->save();
            throw $except;
        }
        $this->import->fill(['import_status' => 'imported', 'message' => null])->save();
        $this->lpInfo("Route data import complete");
        return self::SUCCESS;
    }

    protected function setupProgressBar(): void
    {
        // Progress bar setup. Initial settings used for "Parse main file".
        ProgressBar::setFormatDefinition('custom', '%percent%% [%bar%]  %elapsed% - %message%');
        $this->progressBar = new ProgressBar($this->output);
        $this->progressBar->setFormat('custom');
        $this->progressBar->setBarCharacter('■');
        $this->progressBar->setEmptyBarCharacter('-');
        $this->progressBar->setProgressCharacter('▪');
    }

    /**
     * Process and import entire route set.
     *
     * @return void
     */
    protected function processFiles(): void
    {
        $database = new NetexDatabase();
        $parser = new NetexFileParser($this->routeSet->getSharedFilePath());

        // Parse all XML files in route set.
        $files = $this->routeSet->getFiles();
        $this->progressBar->setMaxSteps(count($files));
        $this->progressBar->start();

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $this->progressBar->setMessage("Processing $filename");
            $this->progressBar->advance();
            if ($filename === $this->routeSet->getSharedFile()) {
                $this->processMainFile($database, $parser);
            } else {
                $parser->parseLineXmlFile($filePath);
                // Update database with data from NeTEx line file.
                $database->writeRoutes($parser->routes);
                $database->writeJourneyPatterns($parser->journeyPatterns);
                $database->writeVehicleJourneys($parser->vehicleJourneys);
            }
        }
        $database->writeLines($parser->lines);
        $this->progressBar->finish();
        $this->newLine();
    }

    protected function processMainFile(NetexDatabase $database, NetexFileParser $parser)
    {
        $parser->parseMainXmlFile();
        $parser->generateCalendar();
        $database->writeCalendar($parser->calendar);
        $database->writeOperators($parser->operators);
        $database->writeGroupOfLines($parser->groupOfLines);
        $database->writeScheduledStopPoints($parser->scheduledStopPoints);
        $database->writeDestinationDisplays($parser->destinationDisplays);
        $database->writeStopAssignments($parser->stopAssignments);
        $database->writeServiceLinks($parser->serviceLinks);
        $database->writeVehicleSchedules($parser->vehicleSchedules);

        // Free up menory.
        unset($parser->calendar);
        unset($parser->operatingPeriods);
        unset($parser->dayTypeAssignments);
        unset($parser->dayTypes);
        unset($parser->operators);
        unset($parser->groupOfLines);
        unset($parser->routePoints);
        unset($parser->scheduledStopPoints);
        unset($parser->stopAssignments);
        unset($parser->destinationDisplays);
        unset($parser->serviceLinks);
        unset($parser->vehicleSchedules);
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
}
