<?php

namespace TromsFylkestrafikk\Netex\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;
use TromsFylkestrafikk\Netex\Models\ActiveCall;
use TromsFylkestrafikk\Netex\Services\RouteBase;
use TromsFylkestrafikk\Netex\Services\RouteValidator;

class RouteActivator extends RouteBase
{
    /**
     * @var string
     */
    protected $fromDate;

    /**
     * @var string
     */
    protected $toDate;

    /**
     * @var string[]
     */
    protected $activationDates = [];

    /**
     * @var \TromsFylkestrafikk\Netex\Services\DbBulkInsert
     */
    protected $journeyDumper;

    /**
     * @var \TromsFylkestrafikk\Netex\Services\DbBulkInsert
     */
    protected $callDumper;

    /**
     * Number of days processed.
     *
     * @var int
     */
    protected $dayCount;

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
     * @var \Closure|null
     */
    protected $dayCallback;

    /**
     * @var \Closure|null
     */
    protected $journeyCallback;

    /**
     * Keep track of seen call IDs to detect duplicates.
     *
     * @var bool[]
     */
    protected $callIds = [];

    /**
     * Keep track of seen journey IDs to detect duplicates.
     *
     * @var bool[]
     */
    protected $aJourneyIds = [];

    /**
     * Error indicator (e.g. for duplicate journey/call IDs).
     */
    protected $hasErrors = false;

    /**
     * Create a new command instance.
     *
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param string|null $set Either 'active' or 'raw'. Active uses dates found in active tables.
     *
     * @return void
     */
    public function __construct($fromDate = null, $toDate = null, $set = null)
    {
        $fromDate = $this->sanitizeDate($fromDate);
        $toDate = $this->sanitizeDate($toDate);
        $dates = DB::table($set === 'active' ? 'netex_active_journeys' : 'netex_calendar')
            ->selectRaw('min(date) as fromDate')
            ->selectRaw('max(date) as toDate')
            ->first();
        $this->fromDate = $fromDate ? max($fromDate, $dates->fromDate) : $dates->fromDate;
        $this->toDate = $toDate ? min($toDate, $dates->toDate) : $dates->toDate;
    }

    /**
     * @return string
     */
    public function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * @return string
     */
    public function getToDate()
    {
        return $this->toDate;
    }

    /**
     * Activate route data for given dates.
     *
     * @return $this
     */
    public function activate()
    {
        $date = new Carbon($this->fromDate);
        $toDate = new Carbon($this->toDate);

        Log::info(sprintf("NeTEx: Activating route data between %s and %s", $this->fromDate, $this->toDate));
        $this->journeyDumper = new DbBulkInsert('netex_active_journeys', 'insertOrIgnore');
        $this->callDumper = new DbBulkInsert('netex_active_calls', 'insertOrIgnore');
        $this->dayCount = 0;
        $this->callFillable = array_flip((new ActiveCall())->getFillable());
        $this->journeyFillable = array_flip((new ActiveJourney())->getFillable());

        $prevJourneyCount = 0;
        $prevCallCount = 0;
        while ($date <= $toDate) {
            $dateStr = $date->format('Y-m-d');
            if (in_array($dateStr, $this->activationDates)) {
                // Reset internal overview of seen IDs
                $this->callIds = [];
                $this->aJourneyIds = [];
                $rawJourneys = self::getRawJourneys($dateStr);
                $this->activateJourneys($dateStr, $rawJourneys);
                $this->dayCount++;
                // Interim flush to assert the full day is complete, and get the
                // correct written count.
                $this->journeyDumper->flush();
                $this->callDumper->flush();
                Log::debug(sprintf(
                    "NeTEx: %s: %d journeys and %d calls",
                    $dateStr,
                    $this->journeyDumper->getRecordsWritten() - $prevJourneyCount,
                    $this->callDumper->getRecordsWritten() - $prevCallCount,
                ));
                $prevJourneyCount = $this->journeyDumper->getRecordsWritten();
                $prevCallCount = $this->callDumper->getRecordsWritten();
            }
            $this->invoke($this->dayCallback, $dateStr);
            $date->addDay();
        }
        // Write last prepared records;
        $this->journeyDumper->flush();
        $this->callDumper->flush();
        Log::info(sprintf(
            "NeTEx: Activation complete. %d days, %d journeys, %d calls",
            $this->dayCount,
            $this->journeyDumper->getRecordsWritten(),
            $this->callDumper->getRecordsWritten()
        ));
        return $this;
    }

    /**
     * Deactivate route set between given dates.
     *
     * @return $this
     */
    public function deactivate($forced = false)
    {
        $date = new Carbon($this->fromDate);
        $toDate = new Carbon($this->toDate);

        $this->dayCount = 0;

        Log::info(sprintf("NeTEx: Deactivating route data between %s and %s", $this->fromDate, $this->toDate));
        while ($date <= $toDate) {
            $dateStr = $date->format('Y-m-d');
            if ($forced || in_array($dateStr, $this->activationDates)) {
                DB::table('netex_active_calls', 'call')
                    ->join('netex_active_journeys as journey', 'call.active_journey_id', '=', 'journey.id')
                    ->whereDate('journey.date', $date)->delete();
                DB::table('netex_active_journeys')->whereDate('date', $date)->delete();
                Log::debug(sprintf("NeTEx: %s: Deactivated.", $dateStr));
                $this->dayCount++;
            }
            $this->invoke($this->dayCallback, $dateStr);
            $date->addDay();
        }
        Log::info(sprintf("NeTEx: Deactivation %s",
            ($this->dayCount > 0) ? 'complete.' : 'skipped.',
        ));
        return $this;
    }

    /**
     * Validate route set for the given period against already activated data.
     *
     * @return $this
     */
    public function validate()
    {
        $date = new Carbon($this->fromDate);
        $toDate = new Carbon($this->toDate);

        $validator = new RouteValidator();
        Log::info(sprintf("NeTEx: Validating route data between %s and %s", $this->fromDate, $this->toDate));

        while ($date <= $toDate) {
            $dateStr = $date->format('Y-m-d');
            $mismatch = $validator->validateJourneys($dateStr);
            if ($mismatch) {
                $this->activationDates[] = $dateStr;
            } else {
                Log::debug(sprintf("NeTEx: Content for %s is matching existing data.", $dateStr));
            }
            $this->invoke($this->dayCallback, $dateStr);
            $date->addDay();
        }
        Log::info(sprintf("NeTEx: Validation complete. Activation required for %d date(s).",
            count($this->activationDates),
        ));
        return $this;
    }

    /**
     * Add handler for completing a full day of vehicle journeys.
     *
     * @param Closure $closure
     *
     * @return $this
     */
    public function onDay(Closure $closure)
    {
        $this->dayCallback = $closure;
        return $this;
    }

    /**
     * Add handler for completing a single journey.
     *
     * @param Closure $closure
     *
     * @return $this
     */
    public function onJourney(Closure $closure)
    {
        $this->journeyCallback = $closure;
        return $this;
    }

    /**
     * Get a summary of processed items.
     *
     * @return int[]
     */
    public function summary()
    {
        return [
            'days' => $this->dayCount,
            'journeys' => $this->journeyDumper->getRecordsWritten(),
            'calls' => $this->callDumper->getRecordsWritten(),
            'errors' => $this->hasErrors,
        ];
    }

    protected function activateJourneys($date, Collection $rawJourneys)
    {
        foreach ($rawJourneys as $rawJourney) {
            $jRec = array_intersect_key((array) $rawJourney, $this->journeyFillable);
            $jRec['date'] = $date;
            $jId = self::makeJourneyId($jRec);
            if (!empty($this->aJourneyIds[$jId])) {
                Log::error(sprintf(
                    'NeTEx: Duplicate active journey ID detected: %s. Journey ID: %s (%s)',
                    $jId,
                    $jRec['vehicle_journey_id'],
                    $jRec['name']
                ));
                $this->hasErrors = true;
                continue;
            }
            $this->aJourneyIds[$jId] = true;
            $jRec['id'] = $jId;
            $this->activateJourneyCalls($jRec);
            $this->journeyDumper->addRecord($jRec);
            $this->invoke($this->journeyCallback, $jRec);
        }
    }

    protected function activateJourneyCalls(array &$jRec)
    {
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
            $this->activateCall($jRec, (array) $rawCall);
        }
        $first = $rawCalls->first();
        $last = $rawCalls->last();
        $jRec['first_stop_quay_id'] = $first->stop_quay_id;
        $jRec['last_stop_quay_id'] = $last->stop_quay_id;
        $jRec['start_at'] = $first->departure_time;
        $jRec['end_at'] = $last->arrival_time;
    }

    /**
     * Activate (persistent store) a single raw call.
     *
     * @param mixed[] $jRec
     * @param mixed[] $rawCall
     */
    protected function activateCall(array $jRec, array $rawCall)
    {
        $callId = self::makeCallId($rawCall, $jRec['id']);
        if (!empty($this->callIds[$callId])) {
            Log::error(sprintf(
                "NeTEx activation: Duplicate active call detected: %s on journey ID %s. Call time: %s (%s)",
                $callId,
                $jRec['vehicle_journey_id'],
                $rawCall['call_time'],
                $rawCall['stop_place_name']
            ));
            $this->hasErrors = true;
            return;
        }
        $this->callIds[$callId] = true;
        $this->callDumper->addRecord(array_merge(
            array_intersect_key($rawCall, $this->callFillable),
            [
                'id' => self::makeCallId($rawCall, $jRec['id']),
                'active_journey_id' => $jRec['id'],
                'line_private_code' => $jRec['line_private_code'],
            ]
        ));
    }

    /**
     * Assert we have a uniform date string format.
     *
     * @param string|null $dateStr
     * @return string|null
     */
    protected function sanitizeDate($dateStr = null)
    {
        return $dateStr ? (new Carbon($dateStr))->format('Y-m-d') : null;
    }

    /**
     * Invoke a callback if it exists.
     *
     * The remaining arguments are passed on as arguments to the handler.
     *
     * @param \Closure $callback
     */
    protected function invoke(Closure $callback = null)
    {
        if (is_callable($callback)) {
            call_user_func_array($callback, array_slice(func_get_args(), 1));
        }
    }
}
