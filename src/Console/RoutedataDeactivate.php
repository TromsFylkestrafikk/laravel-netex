<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use TromsFylkestrafikk\Netex\Console\Traits\ActivateProgress;
use TromsFylkestrafikk\Netex\Models\Import;
use TromsFylkestrafikk\Netex\Services\RouteActivator;

class RoutedataDeactivate extends Command
{
    use ActivateProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:routedata-deactivate
                            {from-date? : Deactivate data from this date}
                            {to-date? : Deactivate route data to this date}
                            {--o|old : Remove old activation data}
                            {--p|purge : Purge activation status entry too}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate route data.';

    /**
     * @var Import
     */
    protected $import;

    /**
     * @var \TromsFylkestrafikk\Netex\Services\RouteActivator
     */
    protected $activator;

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
     * @return mixed
     */
    public function handle()
    {
        $this->import = Import::latest()->first();
        if (!$this->import) {
            $this->error('Deactivate: Import some route data before deactivating');
            return self::FAILURE;
        }
        $initStatus = $this->initActivator();
        if ($initStatus !== self::SUCCESS) {
            return $initStatus;
        }
        if ($this->activator->getFromDate() > $this->activator->getToDate()) {
            $this->info('Deactivate: Nothing to do');
            return self::SUCCESS;
        }
        $this->info(sprintf(
            "Deactivating routedata between %s and %s",
            $this->activator->getFromDate(),
            $this->activator->getToDate()
        ));
        $this->setupProgressBar();
        $this->progressBar->start();
        $this->activator
            ->purge($this->option('purge'))
            ->onDay(function ($date) {
                $this->progressBar->advance();
                $this->progressBar->setMessage($date);
            })
            ->deactivate();
        $this->progressBar->finish();
        return self::SUCCESS;
    }

    protected function initActivator(): int
    {
        if ($this->option('old')) {
            if ($this->argument('from-date')) {
                $this->error('Cannot use dates with --old');
                return self::FAILURE;
            }
            $fromDate = null;
            $toDate = today()->subDays(1)->format('Y-m-d');
        } else {
            $fromDate = $this->argument('from-date');
            $toDate = $this->argument('to-date');
        }
        $this->activator = new RouteActivator($this->import, $fromDate, $toDate, 'active');
        return self::SUCCESS;
    }
}
