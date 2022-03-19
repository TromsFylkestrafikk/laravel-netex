<?php

namespace TromsFylkestrafikk\Netex\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;
use TromsFylkestrafikk\Netex\Models\ActiveCall;

class RouteActivator
{
    /**
     * Number of records to cache before doing a DB::insert()
     *
     * @var int
     */
    public const RECORDS_BEFORE_WRITE = 2000;

    /**
     * @var string
     */
    protected $fromDate;

    /**
     * @var string
     */
    protected $toDate;

    /**
     * Non-written journey records.
     *
     * @var array[]
     */
    protected $nwJourneyRecords;

    /**
     * Non-written journey records count.
     *
     * @var int
     */
    protected $nwJourneyCount;

    /**
     * Non-written call records.
     *
     * @var array[]
     */
    protected $nwCallRecords;

    /**
     * Non-written call records count.
     *
     * @var int
     */
    protected $nwCallCount;

    /**
     * Total number of calls written.
     *
     * @var int
     */
    protected $callCount;

    /**
     * Number of journeys written.
     *
     * @var int
     */
    protected $journeyCount;

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
     * Create a new command instance.
     *
     * @param string|null $fromDate
     * @param string|null $toDate
     *
     * @return void
     */
    public function __construct($fromDate = null, $toDate = null)
    {
        $fromDate = $this->sanitizeDate($fromDate);
        $toDate = $this->sanitizeDate($toDate);
        $dates = DB::table('netex_calendar')
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
        $this->dayCount = 0;
        $this->journeyCount = 0;
        $this->callCount = 0;
        $this->nwJourneyRecords = [];
        $this->nwJourneyCount = 0;
        $this->nwCallRecords = [];
        $this->nwCallCount = 0;
        $this->callFillable = array_flip((new ActiveCall())->getFillable());
        $this->journeyFillable = array_flip((new ActiveJourney())->getFillable());

        while ($date <= $toDate) {
            $dateStr = $date->format('Y-m-d');
            $rawJourneys = $this->getRawJourneys($dateStr);
            $this->activateJourneys($dateStr, $rawJourneys);
            $this->dayCount++;
            $this->invoke($this->dayCallback, $dateStr);
            $date->addDay();
        }
        // Write last prepared records;
        $this->addJourneyRecord();
        $this->addCallRecord();
        return $this;
    }

    /**
     * Deactivate route set between given dates.
     *
     * @return $this
     */
    public function deactivate()
    {
        $date = new Carbon($this->fromDate);
        $toDate = new Carbon($this->toDate);

        $this->dayCount = 0;
        $this->journeyCount = 0;
        $this->callCount = 0;

        while ($date <= $toDate) {
            DB::table('netex_active_calls', 'call')
                ->join('netex_active_journeys as journey', 'call.active_journey_id', '=', 'journey.id')
                ->whereDate('journey.date', $date)->delete();
            DB::table('netex_active_journeys')->whereDate('date', $date)->delete();
            $this->dayCount++;
            $this->invoke($this->dayCallback, $date->format('Y-m-d'));
            $date->addDay();
        }
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
            'journeys' => $this->journeyCount,
            'calls' => $this->callCount,
        ];
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

    protected function activateJourneys($date, Collection $rawJourneys)
    {
        foreach ($rawJourneys as $rawJourney) {
            $jRec = array_intersect_key((array) $rawJourney, $this->journeyFillable);
            $jRec['date'] = $date;
            $jRec['id'] = $this->makeJourneyId($jRec);
            $this->activateJourneyCalls($jRec);
            $this->addJourneyRecord($jRec);
            $this->journeyCount++;
            $this->invoke($this->journeyCallback, $jRec);
        }
    }

    protected function activateJourneyCalls(array &$jRec)
    {
        $rawCalls = $this->getRawCalls($jRec['journey_ref']);
        $prevDeparture = new Carbon("{$jRec['date']} 00:00:00");
        $prevArrival = new Carbon("{$jRec['date']} 00:00:00");
        foreach ($rawCalls as $rawCall) {
            if ($rawCall->departure_time) {
                $departure = new Carbon("{$jRec['date']} {$rawCall->departure_time}");
                $rawCall->departure_time = $this->makeIsoDate($prevDeparture, $departure);
                $prevDeparture = $departure;
            }
            if ($rawCall->arrival_time) {
                $arrival = new Carbon("{$jRec['date']} {$rawCall->arrival_time}");
                $rawCall->arrival_time = $this->makeIsoDate($prevArrival, $arrival);
                $prevArrival = $arrival;
            }
            $rawCall->call_time = $rawCall->arrival_time ?: $rawCall->departure_time;
            $this->activateCall($jRec, (array) $rawCall);
        }
        $first = $rawCalls->first();
        $last = $rawCalls->last();
        $jRec['first_stop_quay_ref'] = $first->quay_ref;
        $jRec['last_stop_quay_ref'] = $last->quay_ref;
        $jRec['start_at'] = $first->departure_time;
        $jRec['end_at'] = $last->arrival_time;
    }

    protected function activateCall(array $jRec, array $rawCall)
    {
        $this->addCallRecord(array_merge(
            array_intersect_key($rawCall, $this->callFillable),
            [
                'id' => $this->makeCallId($rawCall, $jRec['id']),
                'active_journey_id' => $jRec['id'],
                'line_private_code' => $jRec['line_private_code'],
            ]
        ));
        $this->callCount++;
    }

    protected function addJourneyRecord($record = null)
    {
        if ($record) {
            $this->nwJourneyRecords[] = $record;
            $this->nwJourneyCount++;
        }

        // Flush unwritten records to persistent storage.
        if (!$record || $this->nwJourneyCount > self::RECORDS_BEFORE_WRITE) {
            DB::table('netex_active_journeys')->insert($this->nwJourneyRecords);
            $this->nwJourneyRecords = [];
            $this->nwJourneyCount = 0;
        }
    }

    protected function addCallRecord($record = null)
    {
        if ($record) {
            $this->nwCallRecords[] = $record;
            $this->nwCallCount++;
        }

        // Flush unwritten records to persistent storage.
        if (!$record || $this->nwCallCount > self::RECORDS_BEFORE_WRITE) {
            DB::table('netex_active_calls')->insert($this->nwCallRecords);
            $this->nwCallRecords = [];
            $this->nwCallCount = 0;
        }
    }

    /**
     * @param string $date
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRawJourneys($date)
    {
        return DB::table('netex_vehicle_journeys', 'journey')
            ->select([
                'journey.id as journey_ref',
                'journey.name',
                'journey.private_code',
                'route.direction',
                'journey.journey_pattern_ref',
                'journey.operator_ref',
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
        $start = microtime(true);
        $ret = DB::table('netex_passing_times', 'ptime')
            ->select([
                'ptime.arrival_time',
                'ptime.departure_time',
                'patstop.alighting',
                'patstop.boarding',
                'patstop.order',
                'stopass.quay_ref',
                'quay.privateCode as quay_private_code',
                'quay.publicCode as quay_public_code',
                'stop.name as stop_place_name',
            ])
            ->join('netex_journey_pattern_stop_point as patstop', 'ptime.journey_pattern_stop_point_ref', '=', 'patstop.id')
            ->join('netex_stop_assignments as stopass', 'patstop.stop_point_ref', '=', 'stopass.id')
            ->join('netex_stop_quay as quay', 'stopass.quay_ref', '=', 'quay.id')
            ->join('netex_stop_place as stop', 'quay.stop_place_id', '=', 'stop.id')
            ->where('ptime.vehicle_journey_ref', '=', $journeyRef)
            ->orderBy('patstop.order')
            ->get();
        return $ret;
    }

    protected function sanitizeDate($dateStr = null)
    {
        return $dateStr ? (new Carbon($dateStr))->format('Y-m-d') : null;
    }

    protected function makeIsoDate($prev, $current)
    {
        if ($current < $prev) {
            $current->addDay();
        }
        return $current->format('Y-m-d H:i:s');
    }

    protected function invoke(Closure $callback = null)
    {
        if (is_callable($callback)) {
            call_user_func_array($callback, array_slice(func_get_args(), 1));
        }
    }
}
