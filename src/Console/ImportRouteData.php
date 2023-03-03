<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;
use TromsFylkestrafikk\Netex\Console\NeTEx\NetexFileParser;
use TromsFylkestrafikk\Netex\Console\NeTEx\NetexDatabase;
use TromsFylkestrafikk\Netex\Models\ImportStatus;
use TromsFylkestrafikk\Netex\Services\StopsActivator;

class ImportRouteData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:importroutedata
                            {path : Folder containing XML files in NeTEx format.}
                            {main : Filename of the main NeTEx file (XML).}
                            {import-id? : ID for import status update.}';

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
        $this->info('Importing route data files...');
        Log::info("NeTEx route data import starting...");
        $importStatus = ImportStatus::find($this->argument('import-id'));

        // Check input parameters.
        $netexDir = rtrim($this->argument('path'), '/');
        $mainXmlFile = sprintf("%s/%s", $netexDir, $this->argument('main'));
        if (strlen($mainXmlFile) < 5) {
            $this->error("Invalid input parameter(s) supplied to import module!");
            Log::error("Invalid input parameter(s) supplied to import module!");
            if ($importStatus) {
                $importStatus->status = static::FAILURE;
                $importStatus->save();
            }
            return self::FAILURE;
        }
        if (strpos($mainXmlFile, '.xml', -4) === false) {
            $this->error("Unrecognized main file extension! Only XML is supported.");
            Log::error("Unrecognized main file extension! Only XML is supported.");
            if ($importStatus) {
                $importStatus->status = static::FAILURE;
                $importStatus->save();
            }
            return self::FAILURE;
        }
        if (!file_exists($mainXmlFile)) {
            $this->error("Main NeTEx XML file ($mainXmlFile) was not found!");
            Log::error("Main NeTEx XML file ($mainXmlFile) was not found!");
            if ($importStatus) {
                $importStatus->status = static::FAILURE;
                $importStatus->save();
            }
            return self::FAILURE;
        }
        $files = array_filter(
            glob($netexDir . '/*.xml', GLOB_NOSORT),
            fn ($path) => $path !== $mainXmlFile
        );
        if (count($files) <= 1) {
            $this->error("No NeTEx line files (XML) found in $netexDir");
            Log::error("No NeTEx line files (XML) found in $netexDir");
            if ($importStatus) {
                $importStatus->status = static::FAILURE;
                $importStatus->save();
            }
            return self::FAILURE;
        }

        $this->setupProgressBar();
        $database = new NetexDatabase();
        $parser = new NetexFileParser($mainXmlFile);
        $this->processMainFile($database, $parser);
        $this->processLineFiles($database, $parser, $files);

        // Update 'active' stops, seen in this data set.
        $this->info("Synchronizing active stops found in route set ...");
        $stopsActivator->update();
        $this->info("Synchronizing complete");
        Log::info("NeTEx route data import ended.");
        return self::SUCCESS;
    }

    protected function setupProgressBar()
    {
        // Progress bar setup. Initial settings used for "Parse main file".
        ProgressBar::setFormatDefinition('custom', ' %bar%  %elapsed% - %message%');
        $this->progressBar = new ProgressBar($this->output);
        $this->progressBar->setFormat('custom');
        $this->progressBar->setBarCharacter('■');
        $this->progressBar->setEmptyBarCharacter('-');
        $this->progressBar->setProgressCharacter('▪');
    }

    protected function processMainFile(NetexDatabase $database, NetexFileParser $parser)
    {
        $this->progressBar->setMaxSteps(9);
        $this->progressBar->start();
        $this->progressBar->setMessage('Processing NeTEx main data.');
        $parser->parseMainxmlFile();
        $this->progressBar->advance();
        $parser->generateCalendar();
        $this->progressBar->advance();

        $database->writeCalendar($parser->calendar);
        $this->progressBar->advance();
        $database->writeOperators($parser->operators);
        $this->progressBar->advance();
        $database->writeGroupOfLines($parser->groupOfLines);
        $this->progressBar->advance();
        $database->writeScheduledStopPoints($parser->scheduledStopPoints);
        $this->progressBar->advance();
        $database->writeDestinationDisplays($parser->destinationDisplays);
        $this->progressBar->advance();
        $database->writeStopAssignments($parser->stopAssignments);
        $this->progressBar->advance();
        $database->writeServiceLinks($parser->serviceLinks);
        $this->progressBar->advance();
        $database->writeVehicleSchedules($parser->vehicleSchedules);
        $this->progressBar->finish();
        $this->newLine();

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

    protected function processLineFiles(NetexDatabase $database, NetexFileParser $parser, $files)
    {
        // Parse all line files.
        $this->progressBar->setMaxSteps(count($files) + 1);
        $this->progressBar->setMessage('Processing NeTEx line data.');
        $this->progressBar->start();

        foreach ($files as $filePath) {
            $parser->parseLineXmlFile($filePath);

            // Update database with data from NeTEx line file.
            $database->writeRoutes($parser->routes);
            $database->writeJourneyPatterns($parser->journeyPatterns);
            $database->writeVehicleJourneys($parser->vehicleJourneys);
            $this->progressBar->advance();
        }

        $database->writeLines($parser->lines);
        $this->progressBar->finish();
        $this->newLine();
    }
}
