<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use TromsFylkestrafikk\Netex\Services\RouteActivator;
use TromsFylkestrafikk\Netex\Console\Traits\ActivateProgress;
use TromsFylkestrafikk\Netex\Models\ImportStatus;

class ActivateRoutedata extends Command
{
    use ActivateProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:activate
                            {from-date? : Activate data from this date}
                            {to-date? : Activate route data up until this date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate netex routedata for fast queries';

    /**
     * @var \TromsFylkestrafikk\Netex\Services\RouteActivator
     */
    protected $activator;

    /**
     * @var int
     */
    protected $journeyCount;

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
        $this->activator = new RouteActivator($this->argument('from-date'), $this->argument('to-date'));
        $this->info(sprintf(
            'Activating route data between %s and %s',
            $this->activator->getFromDate(),
            $this->activator->getToDate()
        ));
        $this->info("Step 1: De-activate");
        $this->setupProgressBar();
        $this->activator
            ->onDay(function ($date) {
                $this->progressBar->setMessage($date);
                $this->progressBar->advance();
            })
            ->deactivate();
        $this->progressBar->finish();

        $this->info("Step 2: Activating");
        $this->setupProgressBar();
        $this->activator
            ->onJourney(fn () => $this->journeyCount++)
            ->onDay(function ($date) {
                $this->progressBar->advance();
                $this->progressBar->setMessage(sprintf("$date: %d journeys", $this->journeyCount));
                $this->journeyCount = 0;
            })
            ->activate();
        $this->progressBar->finish();
        $stats = $this->activator->summary();
        $this->info(sprintf(
            "Activation complete. %d days, %d journeys, %d calls",
            $stats['days'],
            $stats['journeys'],
            $stats['calls']
        ));
        if ($stats['errors']) {
            $importStatus = new ImportStatus();
            $importStatus->setError();
        }
        return static::SUCCESS;
    }
}
