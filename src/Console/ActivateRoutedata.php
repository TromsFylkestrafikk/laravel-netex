<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;
use TromsFylkestrafikk\Netex\Models\ActiveCall;

class ActivateRoutedata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:activate
                            {from-date? : Activate data from this date}
                            {to-date? : Activate route data up until this date}';

    /**
     * @var string
     */
    protected $fromDate;

    /**
     * @var string
     */
    protected $toDate;

    /**
     * Call records not yet written to persistent storage.
     *
     * @var array[]
     */
    protected $callRecords;

    /**
     * Number of call records not yet written.
     *
     * @var int
     */
    protected $callCount;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate netex routedata for fast queries';

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
        $this->initDates();
        $this->deactivate();
        $this->activate();
        return static::SUCCESS;
    }

    protected function activate()
    {
        $date = new Carbon($this->fromDate);
        $toDate = new Carbon($this->toDate);
        $this->callRecords = [];
        $this->callCount = 0;

        while ($date <= $toDate) {
            $dateStr = $date->format('Y-m-d');
            $rawJourneys = $this->getRawJourneys($dateStr);
            $this->info(sprintf("Journeys found for day %s: %d", $date->format('Y-m-d'), $rawJourneys->count()));
            $this->activateJourneys($dateStr, $rawJourneys);
            $date->addDay();
        }
    }

    protected function activateJourneys($date, Collection $rawJourneys)
    {
        foreach ($rawJourneys as $rawJourney) {
            $rawJourney->date = $date;
            $this->activateJourney($rawJourney);
        }
    }

    protected function activateJourney($rawJourney)
    {
        $journey = new ActiveJourney((array) $rawJourney);
        $journey->save();
        $this->activateJourneyCalls($journey);
    }

    protected function activateJourneyCalls(ActiveJourney $journey)
    {
        $rawCalls = $this->getRawCalls($journey->journey_ref);
        $prevDeparture = new Carbon("{$journey->date} 00:00:00");
        $prevArrival = new Carbon("{$journey->date} 00:00:00");
        foreach ($rawCalls as $rawCall) {
            if ($rawCall->departure_time) {
                $departure = new Carbon("{$journey->date} {$rawCall->departure_time}");
                $rawCall->departure_time = $this->makeIsoDate($prevDeparture, $departure);
                $prevDeparture = $departure;
            }
            if ($rawCall->arrival_time) {
                $arrival = new Carbon("{$journey->date} {$rawCall->arrival_time}");
                $rawCall->arrival_time = $this->makeIsoDate($prevArrival, $arrival);
                $prevArrival = $arrival;
            }
            $rawCall->call_time = $rawCall->arrival_time ?: $rawCall->departure_time;
            $this->activateCall($journey, $rawCall);
        }
    }

    protected function activateCall(ActiveJourney $journey, $rawCall)
    {
        $call = new ActiveCall((array) $rawCall);
        $call->active_journey_id = $journey->id;
        $call->line_private_code = $journey->line_private_code;
        $call->save();
    }

    protected function makeIsoDate($prev, $current)
    {
        if ($current < $prev) {
            $current->addDay();
        }
        return $current->format('Y-m-d H:i:s');
    }

    protected function deactivate()
    {
        $date = new Carbon($this->fromDate);
        $toDate = new Carbon($this->toDate);

        $this->info('Deactivating days …');
        while ($date <= $toDate) {
            DB::table('netex_active_calls', 'call')
                ->join('netex_active_journeys as journey', 'call.active_journey_id', '=', 'journey.id')
                ->whereDate('journey.date', $date)->delete();
            DB::table('netex_active_journeys')->whereDate('date', $date)->delete();
            $date->addDay();
        }
    }

    protected function initDates()
    {
        $fromDate = $this->argument('from-date');
        $toDate = $this->argument('to-date');
        $dates = DB::table('netex_calendar')
            ->selectRaw('min(date) as fromDate')
            ->selectRaw('max(date) as toDate')
            ->first();
        $this->fromDate = $fromDate ? max($fromDate, $dates->fromDate) : $dates->fromDate;
        $this->toDate = $toDate ? min($toDate, $dates->toDate) : $dates->toDate;
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
        return DB::table('netex_passing_times', 'ptime')
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
    }
}
