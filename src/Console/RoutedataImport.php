<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use TromsFylkestrafikk\Netex\Console\Traits\LogAndPrint;
use TromsFylkestrafikk\Netex\Services\StopsActivator;
use TromsFylkestrafikk\Netex\Services\RouteSet;
use TromsFylkestrafikk\Netex\Services\RouteImporter;
use TromsFylkestrafikk\Netex\Services\RouteImporter\NetexImporterBase;

class RoutedataImport extends Command
{
    use LogAndPrint;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:routedata-import
                            {path : Full path to directory contining route set XML files}
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
     * @var RouteSet
     */
    protected $routeSet;

    /**
     * @var RouteImporter
     */
    protected $importer;

    /**
     * @var StopsActivator
     */
    protected $stopsActivator;

    /**
     * Number of line XML files processed.
     *
     * @var integer
     */
    protected $linesProcessed = 0;

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
        $netexDir = realpath($this->argument('path'));
        $this->routeSet = new RouteSet($netexDir);
        $this->importer = new RouteImporter($this->routeSet);
        if (!$this->option('force') && $this->importer->isImported()) {
            $this->lpInfo("Route set already imported: Not importing. Use --force to override");
            return self::SUCCESS;
        }
        $this->setupProgressBar();
        $this->importer->addProcessedHandler(function (NetexImporterBase $importer, int $count) {
            $this->progressBar->setMessage(sprintf("%s imported", basename($importer->getFile())));
            $this->progressBar->advance();
        })->importSet();
        $this->progressBar->finish();
        $this->newLine();
        return self::SUCCESS;
    }

    protected function setupProgressBar(): void
    {
        // Progress bar setup. Initial settings used for "Parse main file".
        ProgressBar::setFormatDefinition('custom', '%percent%% [%bar%]  %elapsed% - %message%');
        $this->progressBar = $this->output->createProgressBar(count($this->routeSet->getFiles()));
        $this->progressBar->setFormat('custom');
    }
}
