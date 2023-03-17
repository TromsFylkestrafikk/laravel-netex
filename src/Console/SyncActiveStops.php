<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use TromsFylkestrafikk\Netex\Services\StopsActivator;

class SyncActiveStops extends Command
{
    public const CHUNK_SIZE = 200;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:sync-active-stops';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Update 'active' state on stops based on existence in route set";

    /**
     * @var \TromsFylkestrafikk\Netex\Services\StopsActivator
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
    public function handle(StopsActivator $activator)
    {
        $this->activator = $activator;

        // First, clear active stops.
        $activator
            ->withDeactProgress($this->stopProgressFactory(
                $this->output->createProgressBar(),
                "Deactivating existing stops."
            ))
            ->withActProgress($this->stopProgressFactory(
                $this->output->createProgressBar(),
                "Activating stops seen in current route set."
            ))
            ->update();
        $this->info("Stop place activation complete.");
    }

    protected function stopProgressFactory(ProgressBar $bar, $startMsg = null)
    {
        return function ($current, $total) use ($bar, $startMsg) {
            if (!$current) {
                if ($startMsg) {
                    $this->info($startMsg);
                }
                $bar->setMaxSteps($total);
                $bar->start();
            } elseif ($current >= $total) {
                $bar->finish();
                $this->newLine();
            } else {
                $bar->setProgress($current);
            }
        };
    }
}
