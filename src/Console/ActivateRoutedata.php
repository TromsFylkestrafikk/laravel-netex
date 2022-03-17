<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use TromsFylkestrafikk\Netex\Services\RouteActivator;

class ActivateRoutedata extends Command
{
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

    protected $journeyCount;

    protected $dayCount;

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
        $activatior = new RouteActivator($this->argument('from-date'), $this->argument('to-date'));
        $this->info(sprintf(
            'Activating route data between %s and %s',
            $activatior->getFromDate(),
            $activatior->getToDate()
        ));
        $activatior->deactivate()
            ->onDay(function ($date) {
                $this->info(sprintf("%s: Activated %d journeys", $date, $this->journeyCount));
                $this->journeyCount = 0;
            })
            ->onJourney(fn () => $this->journeyCount++)
            ->activate();
        $stats = $activatior->summary();
        $this->info(sprintf(
            "Activation complete. %d days, %d journeys, %d calls",
            $stats['days'],
            $stats['journeys'],
            $stats['calls']
        ));
        return static::SUCCESS;
    }
}
