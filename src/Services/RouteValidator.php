<?php

namespace TromsFylkestrafikk\Netex\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Models\ActiveCall;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;

class RouteValidator
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
     * Validate the newly imported data against existing tables and append
     * dates with mismatching routedata to to the "activationDates" array.
     *
     * @param string $endDate
     */
    public function validateActivationPeriod($endDate)
    {
        Log::debug('Validating routedata.');
        $date = Carbon::now()->format('Y-m-d');
        do {
            $mismatch = $this->validateJourneys($date);
            if ($mismatch) {
                $this->activationDates[] = $date;
            } else {
                Log::debug(sprintf("Content for %s is matching existing data.", $date));
                if (is_callable($this->onMatchingContentCallback)) {
                    call_user_func($this->onMatchingContentCallback, $date);
                }
            }
            $date = Carbon::parse($date)->addDay()->format('Y-m-d');
        } while ($date <= $endDate);
        Log::debug('Validation completed.');
    }

    /**
     * Validate journeys on a given date.
     *
     * @param string $date
     */
    protected function validateJourneys($date)
    {
        $oldJourneys = DB::table('netex_active_journeys')->where('date', $date)->get();
        $rawJourneys = $this->getRawJourneys($date);
        if ($rawJourneys->count() !== $oldJourneys->count()) {
            // Data size mismatch.
            return true;
        }

        foreach ($rawJourneys as $rawJourney) {
            $jRec = array_intersect_key((array) $rawJourney, $this->journeyFillable);
            $jRec['date'] = $date;
            $jRec['id'] = $this->makeJourneyId($jRec);

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
            $diff = array_diff_assoc($jRec, (array)$oldJourney);
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
        $oldCalls = DB::table('netex_active_calls')->where('active_journey_id', $jRec['id'])->get();
        $rawCalls = $this->getRawCalls($jRec['vehicle_journey_id']);
        $callStamp = new Carbon("{$jRec['date']} 04:00:00");
        $prevDestDisplay = $jRec['name'];
        foreach ($rawCalls as $rawCall) {
            $callStamp = $this->expandCallTime($rawCall, 'arrival_time', $callStamp);
            $callStamp = $this->expandCallTime($rawCall, 'departure_time', $callStamp);
            if ($rawCall->destination_display) {
                $prevDestDisplay = $rawCall->destination_display;
            } else {
                $rawCall->destination_display = $prevDestDisplay;
            }
            $rawCall->call_time = $rawCall->arrival_time ?: $rawCall->departure_time;

            $callId = $this->makeCallId((array)$rawCall, $jRec['id']);
            $callData = array_merge(
                array_intersect_key((array)$rawCall, $this->callFillable),
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
            $diff = array_diff_assoc($callData, (array)$oldCall);
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
     * Query the raw netex data for a day's journey data
     *
     * @param string $date
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRawJourneys($date)
    {
        return DB::table('netex_vehicle_journeys', 'journey')
            ->select([
                'journey.id as vehicle_journey_id',
                'journey.name',
                'journey.private_code',
                'route.direction',
                'journey.journey_pattern_ref',
                'journey.operator_ref as operator_id',
                'line.id as line_id',
                'line.public_code as line_public_code',
                'line.private_code as line_private_code',
                'line.name as line_name',
                'line.transport_mode',
                'line.transport_submode',
            ])
            ->join('netex_journey_patterns as pattern', 'journey.journey_pattern_ref', '=', 'pattern.id')
            ->join('netex_routes as route', 'pattern.route_ref', 'route.id')
            ->join('netex_lines as line', 'journey.line_ref', 'line.id')
            ->whereIn('journey.calendar_ref', function (\Illuminate\Database\Query\Builder $query) use ($date) {
                $query->select('ref')->from('netex_calendar')->whereDate('date', '=', $date);
            })
            ->orderBy('line.private_code')
            ->orderBy('journey.private_code')
            ->get();
    }

    /**
     * @param string $journeyRef
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRawCalls($journeyRef)
    {
        $ret = DB::table('netex_passing_times', 'ptime')
            ->select([
                'ptime.arrival_time',
                'ptime.departure_time',
                'patstop.alighting',
                'patstop.boarding',
                'patstop.order',
                'ddisp.front_text as destination_display',
                'stopass.quay_ref as stop_quay_id',
                'quay.privateCode as quay_private_code',
                'quay.publicCode as quay_public_code',
                'stop.name as stop_place_name',
            ])
            ->join('netex_journey_pattern_stop_point as patstop', 'ptime.journey_pattern_stop_point_ref', '=', 'patstop.id')
            ->leftJoin('netex_destination_displays as ddisp', 'patstop.destination_display_ref', '=', 'ddisp.id')
            ->join('netex_stop_assignments as stopass', 'patstop.stop_point_ref', '=', 'stopass.id')
            ->join('netex_stop_quay as quay', 'stopass.quay_ref', '=', 'quay.id')
            ->join('netex_stop_place as stop', 'quay.stop_place_id', '=', 'stop.id')
            ->where('ptime.vehicle_journey_ref', '=', $journeyRef)
            ->orderBy('patstop.order')
            ->get();
        return $ret;
    }

    /**
     * @param mixed[] $journeyRecord
     *
     * @return string
     */
    public function makeJourneyId(array $journeyRecord)
    {
        return implode(':', [
            $journeyRecord['date'],
            $journeyRecord['line_private_code'],
            $journeyRecord['private_code'],
        ]);
    }

    public function makeCallId(array $callRecord, $journeyRecord)
    {
        return (is_array($journeyRecord)
                ? $this->makeJourneyId($journeyRecord)
                : $journeyRecord)
            . ':'
            . $callRecord['order'];
    }

    /**
     * Expand a time only ('HH:mm:ss') time format to full date timestamp.
     *
     * For calls that passes midnight, we need to keep track of the date and
     * update it when needed. This updated Carbon timestamp is returned.
     *
     * @param \stdClass &$rawCall
     * @param string $property
     * @param \Illuminate\Support\Carbon $prevCallStamp
     *
     * @return \Illuminate\Support\Carbon
     */
    protected function expandCallTime(&$rawCall, $property, $prevCallStamp)
    {
        if (!$rawCall->$property) {
            return $prevCallStamp;
        }
        $dateStr = $prevCallStamp->format('Y-m-d');
        $callStamp = new Carbon("$dateStr {$rawCall->$property}");
        if ($callStamp < $prevCallStamp) {
            $callStamp->addDay();
        }
        $rawCall->$property = $callStamp->format('Y-m-d H:i:s');
        return $callStamp;
    }
}
