<?php

namespace TromsFylkestrafikk\Netex\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Models\ActiveCall;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;
use TromsFylkestrafikk\Netex\Services\RouteBase;

class RouteValidator extends RouteBase
{
    /**
     * Array of dates to be activated.
     */
    public $activationDates = [];

    /**
     * Callback function with $date parameter for matching content event.
     */
    public $onMatchingContentCallback;

    /**
     * Flipped version of ActiveJourney::fillable.
     *
     * @var array
     */
    protected $journeyFillable;

    /**
     * Flipped version of ActiveCall::fillable.
     *
     * @var array
     */
    protected $callFillable;

    /**
     * Create a new class interface.
     *
     * @return void
     */
    public function __construct()
    {
        $this->callFillable = array_flip((new ActiveCall())->getFillable());
        $this->journeyFillable = array_flip((new ActiveJourney())->getFillable());
    }

    /**
     * Validate journeys on a given date.
     *
     * @param string $date
     */
    public function validateJourneys($date)
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
            $jRec['id'] = self::makeJourneyId($jRec);

            $mismatch = $this->validateJourneyCalls($jRec);
            if ($mismatch) {
                return true;
            }

            // Find and remove journey from old collection.
            $id = $jRec['id'];
            $index = $oldJourneys->search(fn ($item) => $item->id === $id);
            if ($index === false) {
                // ID not found in existing database table.
                return true;
            }
            $oldJourney = $oldJourneys->pull($index);

            // Compare old and new journey data.
            $diff = array_diff_assoc($jRec, (array) $oldJourney);
            if (count($diff) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate calls on a given journey.
     *
     * @param array &$jRec
     */
    protected function validateJourneyCalls(array &$jRec)
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
}
