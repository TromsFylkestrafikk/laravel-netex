<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use TromsFylkestrafikk\Netex\Console\Traits\ActivateProgress;
use TromsFylkestrafikk\Netex\Models\Import;
use TromsFylkestrafikk\Netex\Services\RouteActivator;

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
        $import = Import::latest()->first();
        if (!$import) {
            $this->error('Import some route data before deactivating');
            return self::FAILURE;
        }
        $this->activator = new RouteActivator($import, $this->argument('from-date'), $this->argument('to-date'), 'active');
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
            ->deactivate();
        $this->progressBar->finish();
        return self::SUCCESS;
    }
}
