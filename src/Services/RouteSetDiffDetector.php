<?php

namespace TromsFylkestrafikk\Netex\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TromsFylkestrafikk\Netex\Services\RouteBase;

/**
 * Detect difference between imported NeTEx data and currently activated data.
 */
class RouteSetDiffDetector extends RouteBase
{
    /**
     * Returns true if imported route set differ from activated data.
     *
     * @param string $date Date to compare route data against.
     */
    public function differ($date): bool
    {
        $oldJourneys = self::getOldJourneys($date);
        $rawJourneys = self::getRawJourneys($date);
        if ($rawJourneys->count() !== $oldJourneys->count()) {
            // Data size mismatch.
            return true;
        }

        foreach ($rawJourneys as $rawJourney) {
            $jRec = array_intersect_key((array) $rawJourney, $this->journeyFillable);
            $jRec['date'] = $date;
            $journeyId = self::makeJourneyId($jRec);
            $jRec['id'] = $journeyId;

            $mismatch = $this->compareJourneyCalls($jRec);
            if ($mismatch) {
                return true;
            }

            // Find and remove journey from old collection.
            if (!$oldJourneys->has($journeyId)) {
                // ID not found in existing database table.
                return true;
            }
            $oldJourney = $oldJourneys->pull($journeyId);

            // Compare old and new journey data.
            $diff = array_diff_assoc($jRec, (array) $oldJourney);
            if (count($diff) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Compare calls on a given journey.
     *
     * @param array $jRec
     */
    protected function compareJourneyCalls(array &$jRec): bool
    {
        $oldCalls = self::getOldCalls($jRec['id']);
        $rawCalls = self::getRawCalls($jRec['vehicle_journey_id']);
        $callStamp = new Carbon("{$jRec['date']} 04:00:00");
        $prevDestDisplay = $jRec['name'];
        foreach ($rawCalls as $rawCall) {
            $callStamp = self::expandCallTime($rawCall, 'arrival_time', $callStamp);
            $callStamp = self::expandCallTime($rawCall, 'departure_time', $callStamp);
            if ($rawCall->destination_display) {
                $prevDestDisplay = $rawCall->destination_display;
            } else {
                $rawCall->destination_display = $prevDestDisplay;
            }
            $rawCall->call_time = $rawCall->arrival_time ?: $rawCall->departure_time;

            $callId = self::makeCallId((array) $rawCall, $jRec['id']);
            $callData = array_merge(
                array_intersect_key((array) $rawCall, $this->callFillable),
                [
                    'id' => $callId,
                    'active_journey_id' => $jRec['id'],
                    'line_private_code' => $jRec['line_private_code'],
                ]
            );

            // Find and remove call from old collection.
            $index = $oldCalls->search(fn ($item) => $item->id === $callId);
            if ($index === false) {
                // ID not found in existing database table.
                return true;
            }
            $oldCall = $oldCalls->pull($index);

            // Compare old and new call data.
            $diff = array_diff_assoc($callData, (array) $oldCall);
            if (count($diff) > 0) {
                return true;
            }
        }
        $first = $rawCalls->first();
        $last = $rawCalls->last();
        $jRec['first_stop_quay_id'] = $first->stop_quay_id;
        $jRec['last_stop_quay_id'] = $last->stop_quay_id;
        $jRec['start_at'] = $first->departure_time;
        $jRec['end_at'] = $last->arrival_time;
        return false;
    }

    /**
     * Query the already activated journey data for the given date.
     *
     * @param string $date
     *
     * @return \Illuminate\Support\Collection
     */
    protected static function getOldJourneys($date): \Illuminate\Support\Collection
    {
        return DB::table('netex_active_journeys')->whereDate('date', $date)->get()->keyBy('id');
    }

    /**
     * Query the already activated stop call data for the given date.
     *
     * @param string $id
     *
     * @return \Illuminate\Support\Collection
     */
    protected static function getOldCalls($id): Collection
    {
        return DB::table('netex_active_calls')->where('active_journey_id', $id)->get()->keyBy('id');
    }
}
