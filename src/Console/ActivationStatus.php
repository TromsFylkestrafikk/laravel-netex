<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use TromsFylkestrafikk\Netex\Console\Helpers\RoutePeriodBar;
use TromsFylkestrafikk\Netex\Models\ActiveCall;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;
use TromsFylkestrafikk\Netex\Services\RouteActivator;

class ActivationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:status
                            {date? : Show status for this date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show status of current route data';

    /**
     * @var \TromsFylkestrafikk\Netex\Services\RouteActivator
     */
    protected $passiveSet;

    /**
     * @var \TromsFylkestrafikk\Netex\Services\RouteActivator
     */
    protected $activeSet;

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
        $this->passiveSet = new RouteActivator();
        $this->activeSet = new RouteActivator(null, null, 'active');
        if ($this->argument('date')) {
            return $this->dayStatus($this->argument('date'));
        } else {
            return $this->overallStatus();
        }
    }

    protected function overallStatus()
    {
        $this->line((new RoutePeriodBar($this->passiveSet, $this->activeSet))->bars());
        $missing = $this->missingDates();
        $this->line(sprintf("Days in active set without active journeys: %d", count($missing)));
        if ($missing) {
            $this->info(sprintf("Missing days: \n\t- %s", implode("\n\t- ", $missing)), 'v');
        }
        return self::SUCCESS;
    }

    protected function dayStatus($dateStr)
    {
        $date = new Carbon($dateStr);
        $journeyCount = ActiveJourney::whereDate('date', $date)->count();
        $callCount = ActiveJourney::whereDate('date', $date)
            ->withCount('activeCalls')
            ->get()
            ->sum('active_calls_count');
        $firstJourney = ActiveJourney::whereDate('date', $date)->first();
        $activationDate = $firstJourney
            ? (new Carbon($firstJourney->created_at))->format('Y-m-d')
            : '-';
        $this->table(['Journeys', 'Calls', 'Activated'], [[$journeyCount, $callCount, $activationDate]]);
    }

    protected function missingDates()
    {
        $current = new Carbon($this->activeSet->getFromDate());
        $end = new Carbon($this->activeSet->getToDate());
        $missing = [];
        while ($current < $end) {
            if (!$this->activeJourneys($current)) {
                $missing[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        return $missing;
    }

    protected function activeJourneys($date = null)
    {
        return $date ? ActiveJourney::whereDate('date', $date)->count() : ActiveJourney::count();
    }
}
