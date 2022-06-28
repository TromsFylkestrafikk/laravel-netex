<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use TromsFylkestrafikk\Netex\Models\StopAssignment;
use TromsFylkestrafikk\Netex\Models\StopPlace;

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
        // First, clear active stops.
        $this->info("De-activating existing stops.");

        $activeCount = StopPlace::whereActive(true)->count();

        $bar = $this->output->createProgressBar($activeCount);
        $bar->start();
        StopPlace::whereActive(true)->chunkById(self::CHUNK_SIZE, function ($stops) use ($bar) {
            StopPlace::whereKey($stops->pluck('id')->toArray())->update(['active' => false]);
            $bar->advance(self::CHUNK_SIZE);
        });
        $bar->finish();
        $this->newLine();

        // Get 'seen' stops with regtopp ID.
        $this->info("Syncing found stops in route data with existing stops");
        $bar = $this->output->createProgressBar(StopAssignment::count());
        $bar->start();
        StopAssignment::select(['id', 'quay_ref'])
            ->with('quay:stop_place_id,id')
            ->chunkById(self::CHUNK_SIZE, function ($assignments) use ($bar) {
                $stopIds = $assignments->keyBy('quay.stop_place_id')->keys();
                StopPlace::whereKey($stopIds)->update(['active' => true]);
                $bar->advance(self::CHUNK_SIZE);
            });
        $bar->finish();
        $this->newLine();
        $this->info("Stop place activation complete.");
    }
}
