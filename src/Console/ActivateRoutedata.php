<?php

namespace TromsFylkestrafikk\Netex\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Models\Import;
use TromsFylkestrafikk\Netex\Services\RouteActivator;
use TromsFylkestrafikk\Netex\Console\Traits\ActivateProgress;

class ActivateRoutedata extends Command
{
    use ActivateProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:activate
                            {from-date? : Activate data from this date.}
                            {to-date? : Activate route data up until this date.}';

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
        try {
            $this->activator = new RouteActivator($this->argument('from-date'), $this->argument('to-date'));
            $this->info(sprintf(
                'Activating route data between %s and %s',
                $this->activator->getFromDate(),
                $this->activator->getToDate()
            ));
            $importInfo = Import::latest('id')->first();
            if ($importInfo?->status !== static::SUCCESS) {
                $msg = sprintf("Activation aborted due to %s", $importInfo ? 'failed import!' : 'absent import info.');
                $this->error($msg);
                Log::error("NeTEx: $msg");
                return Command::FAILURE;
            }
            if ($importInfo->activated && $this->checkActivationPeriod($importInfo)) {
                $msg = 'Activation skipped. Already activated.';
                $this->warn($msg);
                Log::warning("NeTEx: $msg");
                return Command::SUCCESS;
            }

            $this->info("Step 1: Validate");
            $this->setupProgressBar();
            $this->activator
                ->onDay(function ($date) {
                    $this->progressBar->setMessage($date);
                    $this->progressBar->advance();
                    $this->progressBar->display();
                })
                ->validate();
            $this->progressBar->finish();

            $this->info("Step 2: Deactivate");
            $this->setupProgressBar();
            $this->activator
                ->onDay(function ($date) {
                    $this->progressBar->setMessage($date);
                    $this->progressBar->advance();
                    $this->progressBar->display();
                })
                ->deactivate();
            $this->progressBar->finish();

            $this->info("Step 3: Activate");
            $this->setupProgressBar();
            $this->activator
                ->onJourney(fn () => $this->journeyCount++)
                ->onDay(function ($date) {
                    $this->progressBar->advance();
                    $this->progressBar->setMessage(sprintf("$date: %d journeys", $this->journeyCount));
                    $this->progressBar->display();
                    $this->journeyCount = 0;
                })
                ->activate();
            $this->progressBar->finish();

            $stats = $this->activator->summary();
            $importInfo->days = $stats['days'];
            $importInfo->calls = $stats['calls'];
            $importInfo->journeys = $stats['journeys'];
            $importInfo->valid_to = $this->activator->getToDate();
            $importInfo->activated = true;
            $importInfo->save();

            $this->info(sprintf(
                "Activation complete. %d days, %d journeys, %d calls",
                $stats['days'],
                $stats['journeys'],
                $stats['calls']
            ));
            if ($stats['errors']) {
                return Command::FAILURE;
            }
        } catch (Exception $e) {
            $this->error(sprintf("Error: %s", $e->getMessage()));
            Log::error(sprintf("NeTEx: %s", $e->getMessage()));
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    /**
     * Check new activation period against the old one.
     *
     * @param Import|null $import
     *
     * @return bool
     */
    protected function checkActivationPeriod(Import $import = null)
    {
        if ($import?->status === static::SUCCESS) {
            $newStartDate = $this->activator->getFromDate();
            $newEndDate = $this->activator->getToDate();
            $oldStartDate = $import->date;
            $oldEndDate = $import->valid_to;
            if (($oldStartDate === null) || ($oldEndDate === null)) {
                // Previous activation period is absent/invalid.
                return false;
            }
            if (($oldStartDate <= $newStartDate) && ($newEndDate <= $oldEndDate)) {
                // New activation period is within the old one.
                return true;
            }
        }
        return false;
    }
}
