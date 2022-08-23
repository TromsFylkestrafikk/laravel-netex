<?php

namespace TromsFylkestrafikk\Netex\Services;

use Closure;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Models\StopAssignment;
use TromsFylkestrafikk\Netex\Models\StopPlace;

/**
 * Service for setting 'active' state on stops seen in route set.
 */
class StopsActivator
{
    public const CHUNK_SIZE = 200;

    protected $deactProgressCb = null;

    protected $actProgressCb = null;

    protected $count;
    protected $progress;

    public function __construct()
    {
        $this->progress = 0;
    }

    /**
     * Perform syncronization update.
     *
     * @return $this
     */
    public function update()
    {
        Log::info("[Netex StopsActivator] Updating active stops found in current route data set");
        $this->count = StopPlace::whereActive(true)->count();
        $this->callProgress($this->deactProgressCb);
        Log::debug("[Netex StopsActivator] Deactivating {$this->count} active stops ...");
        StopPlace::whereActive(true)->chunkById(self::CHUNK_SIZE, function ($stops) {
            $this->progress += $stops->count();
            StopPlace::whereKey($stops->pluck('id')->toArray())->update(['active' => false]);
            $this->callProgress($this->deactProgressCb);
        });

        $stopsUpdated = 0;
        $this->progress = 0;
        $this->count = StopAssignment::count();
        Log::debug("[Netex StopsActivator] De-activation complete. Activating using {$this->count} StopAssignments.");
        // Get 'seen' stops with regtopp ID.
        $this->callProgress($this->actProgressCb);
        StopAssignment::select(['id', 'quay_ref'])
            ->with('quay:stop_place_id,id')
            ->chunkById(self::CHUNK_SIZE, function ($assignments) use (&$stopsUpdated) {
                $this->progress += $assignments->count();
                $stopIds = $assignments->keyBy('quay.stop_place_id')->keys();
                $stopsUpdated += count($stopIds);
                StopPlace::whereKey($stopIds)
                    ->update(['active' => true]);
                $this->callProgress($this->actProgressCb);
            });
        Log::info("[Netex StopsActivator] Activation complete. Found ${stopsUpdated} active stop places");
        return $this;
    }

    /**
     * @param Closure $callback Call this during deactivation progress
     *
     * @return $this
     */
    public function withDeactProgress(Closure $callback)
    {
        $this->deactProgressCb = $callback;
        return $this;
    }

    /**
     * @param Closure $callback Call this during activation progress
     *
     * @return $this
     */
    public function withActProgress(Closure $callback)
    {
        $this->actProgressCb = $callback;
        return $this;
    }

    /**
     * Invoke given callback with current progress.
     *
     * @param Closure $callback
     *
     * @return $this
     */
    protected function callProgress(Closure $callback = null)
    {
        if ($callback) {
            call_user_func($callback, $this->progress, $this->count);
        }
        return $this;
    }
}
