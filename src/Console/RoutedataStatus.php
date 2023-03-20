<?php

namespace TromsFylkestrafikk\Netex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use TromsFylkestrafikk\Netex\Console\Helpers\RoutePeriodBar;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;
use TromsFylkestrafikk\Netex\Models\ActiveStatus;
use TromsFylkestrafikk\Netex\Models\Import;
use TromsFylkestrafikk\Netex\Services\RouteActivator;

class RoutedataStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'netex:routedata-status
                            {date? : Show status for this date}
                            {--d|detail : Show detailed day-to-day table status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show activation status of current route data';

    /**
     * @var Import
     */
    protected $import;

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
     * @return int
     */
    public function handle(): int
    {
        $this->import = Import::imported()->latest()->first();
        if (!$this->import) {
            $this->error('Import some route data before deactivating');
            return self::FAILURE;
        }
        $this->passiveSet = new RouteActivator($this->import);
        $this->activeSet = new RouteActivator($this->import, null, null, 'active');
        if (!$this->activeSet->getFromDate()) {
            $this->warn('No active route date. Activate some with artisan netex:activate');
            return self::SUCCESS;
        }
        if ($this->argument('date')) {
            $this->dayStatus($this->argument('date'));
            return self::SUCCESS;
        }
        $this->overallActiveStatus();
        $this->periodBars();
        if ($this->option('detail')) {
            $this->detailedStatus();
        } else {
            $this->listedStatus();
        }
        return self::SUCCESS;
    }

    protected function periodBars(): void
    {
        $this->line((new RoutePeriodBar($this->passiveSet, $this->activeSet))->bars());
    }

    protected function overallActiveStatus(): void
    {
        $this->newLine();
        if ($this->activeSet->isActive()) {
            $this->info('[ OK ] Route set is active within configured period');
        } else {
            $this->warn('[ WARNING ] Route set is NOT active within configured period');
        }
        $this->newLine();
    }

    /**
     * Aggregated list of activation statuses
     */
    protected function listedStatus(): void
    {
        $stats = ActiveStatus::orderBy('id')->get()->keyBy('id');
        $current = new Carbon($this->activeSet->getFromDate());
        $end = new Carbon($this->activeSet->getToDate());
        $prevSet = null;
        $prevStatus = null;
        $startDate = null;
        $days = 0;
        $headers = ['Date', 'Route set', 'Status', 'Days forward'];
        $rows = [];
        while ($current < $end) {
            $date = $current->format('Y-m-d');
            $set = empty($stats[$date]) ? '<missing>' : $stats[$date]->import_id;
            $status = empty($stats[$date]) ? '<missing>' : $stats[$date]->status;
            if ($prevSet !== $set || $prevStatus !== $status) {
                if ($days > 0) {
                    $rows[] = [$startDate, $prevSet, $prevStatus, $days];
                }
                $startDate = $date;
                $days = 1;
            } else {
                $days++;
            }
            $prevSet = $set;
            $prevStatus = $status;
            $current->addDay();
        }
        if ($days > 0) {
            $rows[] = [$startDate, $prevSet, $prevStatus, $days];
        }
        $this->table($headers, $rows);
    }

    /**
     * Print day-to-day activation status as table
     */
    protected function detailedStatus(): void
    {
        $this->table([
            'Date',
            'Route set',
            'Journeys',
            'Calls',
            'Status',
            'Created',
            'Updated',
        ], ActiveStatus::select(
            'id',
            'import_id',
            'journeys',
            'calls',
            'status',
            'created_at',
            'updated_at'
        )->orderBy('id')->get()->keyBy('id')->toArray());
    }

    /**
     * @param $dateStr
     * @return void
     */
    protected function dayStatus($dateStr): void
    {
        $status = ActiveStatus::find($dateStr);
        if (!$status) {
            return;
        }
        $this->table(['Journeys', 'Calls', 'Activated'], [[$status->journeys, $status->calls, $status->updated_at]]);
    }
}
