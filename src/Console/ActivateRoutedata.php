<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;

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
        $this->activateDays();
        return static::SUCCESS;
    }

    protected function activateDays()
    {
        $date = new Carbon($this->fromDate);
        $toDate = new Carbon($this->toDate);

        while ($date <= $toDate) {
            $rawJourneys = $this->getRawJourneys($date->format('Y-m-d'));
            $this->info(sprintf("Journeys found for day %s: %d", $date->format('Y-m-d'), $rawJourneys->count()));
            $this->activateJourneys($rawJourneys);
            $date->addDay();
        }
    }

    protected function activateJourneys(Collection $rawJourneys)
    {
        foreach ($rawJourneys as $rawJourney) {
            $this->activateJourney($rawJourney);
        }
    }

    protected function activateJourney($rawJourney)
    {
        $journey = new ActiveJourney((array) $rawJourney);
        $journey->save();
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
                'cal.date',
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
            ->join('netex_calendar as cal', 'journey.calendar_ref', 'cal.ref')
            ->where('cal.date', '=', $date)
            ->orderBy('line.private_code')
            ->orderBy('journey.private_code')
            ->get();
    }
}
