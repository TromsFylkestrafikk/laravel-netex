<?php

namespace TromsFylkestrafikk\Netex\Services;

use Closure;
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
        $this->count = StopPlace::whereActive(true)->count();
        $this->callProgress($this->deactProgressCb);
        StopPlace::whereActive(true)->chunkById(self::CHUNK_SIZE, function ($stops) {
            $this->progress += $stops->count();
            StopPlace::whereKey($stops->pluck('id')->toArray())->update(['active' => false]);
            $this->callProgress($this->deactProgressCb);
        });

        $this->progress = 0;
        $this->count = StopAssignment::count();
        // Get 'seen' stops with regtopp ID.
        $this->callProgress($this->actProgressCb);
        StopAssignment::select(['id', 'quay_ref'])
            ->with('quay:stop_place_id,id')
            ->chunkById(self::CHUNK_SIZE, function ($assignments) {
                $this->progress += $assignments->count();
                StopPlace::whereKey($assignments->keyBy('quay.stop_place_id')->keys())
                    ->update(['active' => true]);
                $this->callProgress($this->actProgressCb);
            });
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
    protected function callProgress(Closure $callback)
    {
        call_user_func($callback, $this->progress, $this->count);
        return $this;
    }
}
