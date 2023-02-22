<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use TromsFylkestrafikk\Netex\Services\RouteActivator;
use TromsFylkestrafikk\Netex\Console\Traits\ActivateProgress;

class DeactivateRoutedata extends Command
{
    use ActivateProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:deactivate
                            {from-date? : De-activate data from this date}
                            {to-date? : De-activate route data to this date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'De-activate route data.';

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
        $this->activator = new RouteActivator($this->argument('from-date'), $this->argument('to-date'), 'active');
        $this->info(sprintf(
            "De-activating routedata between %s and %s",
            $this->activator->getFromDate(),
            $this->activator->getToDate()
        ));
        $this->setupProgressBar();
        $this->progressBar->start();
        $this->activator
            ->onDay(function ($date) {
                $this->progressBar->advance();
                $this->progressBar->setMessage($date);
            })
            ->deactivate(true);
        $this->progressBar->finish();
        return self::SUCCESS;
    }
}
