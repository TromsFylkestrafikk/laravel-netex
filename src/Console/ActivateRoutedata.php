<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use TromsFylkestrafikk\Netex\Services\RouteActivation;

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

    /**
     * @var string
     */
    protected $fromDate;

    /**
     * @var string
     */
    protected $toDate;

    /**
     * Call records not yet written to persistent storage.
     *
     * @var array[]
     */
    protected $callRecords;

    /**
     * Number of call records not yet written.
     *
     * @var int
     */
    protected $callCount;

    /**
     * Flipped version of ActiveCall::fillable.
     *
     * @var array
     */
    protected $callFillable;

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
        $activatior = new RouteActivation($this->argument('from-date'), $this->argument('to-date'));
        $activatior->deactivate();
        $activatior->activate();
        return static::SUCCESS;
    }
}
