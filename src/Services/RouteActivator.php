<?php

namespace TromsFylkestrafikk\Netex\Services;

use Closure;
use DateInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Services\RouteBase;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;
use TromsFylkestrafikk\Netex\Models\ActiveStatus;
use TromsFylkestrafikk\Netex\Models\Import;

class RouteActivator extends RouteBase
{
    /**
     * @var \TromsFylkestrafikk\Netex\Models\Import
     */
    protected $import;

    /**
     * @var string
     */
    protected $fromDate;

    /**
     * @var string
     */
    protected $toDate;

    /**
     * Whether to ignore detection of unmodified active data.
     *
     * @var bool
     */
    protected $forceActivation = false;

    /**
     * Skip activating days with data present.
     *
     * @var bool
     */
    protected $activateMissingOnly = false;

    /**
     * @var bool
     */
    protected $purgeStatus = false;

    /**
     * @var \TromsFylkestrafikk\Netex\Services\DbBulkInsert
     */
    protected $journeyDumper = null;

    /**
     * @var \TromsFylkestrafikk\Netex\Services\DbBulkInsert
     */
    protected $callDumper = null;

    /**
     * @var \TromsFylkestrafikk\Netex\Services\RouteSetDiffDetector
     */
    protected $diffDetector = null;

    /**
     * Number of days processed.
     *
     * @var int
     */
    protected $dayCount = 0;

    /**
     * @var \Closure|null
     */
    protected $dayCallback = null;

    /**
     * @var \Closure|null
     */
    protected $journeyCallback = null;

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
     *
     * @var bool
     */
    protected $hasErrors = false;

    /**
     * @var string
     */
    protected $dayActivationStatus = null;

    /**
     * Create a new command instance.
     *
     * @param Import $import What route set the activation belongs to
     * @param string|null $fromDate Activation from date
     * @param string|null $toDate Activation to date (including)
     * @param string|null $set Either 'active' or 'raw'. Active uses dates found in active tables.
     *
     * @return void
     */
    public function __construct(Import $import, $fromDate = null, $toDate = null, $set = null)
    {
        parent::__construct();
        $this->import = $import;
        $fromDate = $this->sanitizeDate($fromDate);
        $toDate = $this->sanitizeDate($toDate);
        $dateCol = $set === 'active' ? 'id' : 'date';
        $dates = DB::table($set === 'active' ? 'netex_active_status' : 'netex_calendar')
            ->selectRaw("min($dateCol) as fromDate")
            ->selectRaw("max($dateCol) as toDate")
            ->first();
        $this->fromDate = $fromDate ? max($fromDate, $dates->fromDate) : $dates->fromDate;
        $this->toDate = $toDate ? min($toDate, $dates->toDate) : $dates->toDate;
    }

    /**
     * Force re-activation of existing active data.
     *
     * @param bool $value
     * @return $this
     */
    public function force($value = true): self
    {
        $this->forceActivation = $value;
        return $this;
    }

    /**
     * Only activate days with empty data.
     *
     * @param bool $value
     * @return $this
     */
    public function missingOnly($value = true): self
    {
        $this->activateMissingOnly = $value;
        return $this;
    }

    /**
     * Purge activation status. Only useful during deactivation.
     *
     * @param bool $value
     * @return $this
     */
    public function purge($value = true): self
    {
        $this->purgeStatus = $value;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFromDate(): string|null
    {
        return $this->fromDate;
    }

    /**
     * @return string|null
     */
    public function getToDate(): string|null
    {
        return $this->toDate;
    }

    /**
     * Add handler for completing a full day of vehicle journeys.
     *
     * @param Closure $closure
     *
     * @return $this
     */
    public function onDay(Closure $closure): self
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
    public function onJourney(Closure $closure): self
    {
        $this->journeyCallback = $closure;
        return $this;
    }

    /**
     * Activate route data for given dates.
     *
     * @return $this
     */
    public function activate(): self
    {
        $date = new Carbon($this->fromDate);
        $toDate = new Carbon($this->toDate);

        Log::info(sprintf("NeTEx: Activating route data between %s and %s", $this->fromDate, $this->toDate));
        $this->assertServices();
        $this->dayCount = 0;
        while ($date <= $toDate) {
            $this->activateDate($date->format('Y-m-d'));
            $date->addDay();
        }
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
    public function deactivate(): self
    {
        $date = new Carbon($this->fromDate);
        $toDate = new Carbon($this->toDate);

        $this->dayCount = 0;

        Log::info(sprintf("NeTEx: Deactivating route data between %s and %s", $this->fromDate, $this->toDate));
        while ($date <= $toDate) {
            $dateStr = $date->format('Y-m-d');
            $status = ActiveStatus::firstOrNew(['id' => $dateStr], ['import_id' => $this->import->id]);
            $status->fill([
                'status' => 'incomplete',
                'journeys' => null,
                'count' => null,
            ])->save();
            $this->destroyActiveDate($dateStr);
            if ($this->purgeStatus) {
                $status->delete();
            } else {
                $status->fill(['status' => 'empty'])->save();
            }
            $this->dayCount++;
            $this->invoke($this->dayCallback, $dateStr, 'deactivated');
            $date->addDay();
        }
        Log::info(sprintf("NeTEx: Deactivation %s", ($this->dayCount > 0) ? 'complete.' : 'skipped.'));
        return $this;
    }

    /**
     * Activate a single day.
     *
     * @param string $date
     * @return $this
     */
    public function activateDate($date): self
    {
        // @var ActiveStatus $status
        $status = ActiveStatus::firstOrNew(['id' => $date], [
            'import_id' => $this->import->id,
            'status' => 'incomplete',
        ]);
        $this->assertServices();
        $prevJourneyCount = $this->journeyDumper->getRecordsWritten();
        $prevCallCount = $this->callDumper->getRecordsWritten();
        if ($this->activationRequired($status)) {
            $status->fill(['status' => 'incomplete'])->save();
            $this->destroyActiveDate($date)->buildActiveDate($date);
            $status->fill([
                'import_id' => $this->import->id,
                'journeys' => $this->journeyDumper->getRecordsWritten() - $prevJourneyCount,
                'calls' => $this->callDumper->getRecordsWritten() - $prevCallCount,
            ]);
        } elseif (!$this->activateMissingOnly) {
            // We assume ownership of this import if we're sure the content is
            // equal. The only exception is with self::missingOnly().
            $status->import_id = $this->import->id;
        }
        $status->fill(['status' => 'activated'])->save();
        Log::debug(sprintf(
            "NeTEx: %s: %s (%d journeys, %d calls)",
            $date,
            $this->dayActivationStatus,
            $status->journeys,
            $status->calls,
        ));
        $this->dayCount++;
        $this->invoke($this->dayCallback, $date, $this->dayActivationStatus);
        return $this;
    }

    /**
     * True if all days from today to configured period is activated
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $date = today();
        $toDate = today()->add(new DateInterval(config('netex.activation_period')));
        $stats = ActiveStatus::where('id', '>=', $date->format('Y-m-d'))
            ->where('id', '<=', $toDate->format('Y-m-d'))
            ->get()
            ->keyBy('id');

        while ($date <= $toDate) {
            $dateStr = $date->format('Y-m-d');
            if (empty($stats[$dateStr])) {
                return false;
            }
            if ($stats[$dateStr]->status !== 'activated' || $stats[$dateStr]->import_id !== $this->import->id) {
                return false;
            }
            $date->addDay();
        }
        return true;
    }

    /**
     * Get a summary of processed items.
     *
     * @return int[]
     */
    public function summary(): array
    {
        return [
            'days' => $this->dayCount,
            'journeys' => $this->journeyDumper->getRecordsWritten(),
            'calls' => $this->callDumper->getRecordsWritten(),
            'errors' => $this->hasErrors,
        ];
    }

    /**
     * Assert services used for activation is fit for fight.
     *
     * @return $this
     */
    protected function assertServices(): self
    {
        if ($this->journeyDumper !== null) {
            return $this;
        }
        $this->journeyDumper = new DbBulkInsert('netex_active_journeys', 'insertOrIgnore');
        $this->callDumper = new DbBulkInsert('netex_active_calls', 'insertOrIgnore');
        $this->diffDetector = new RouteSetDiffDetector();
        return $this;
    }

    /**
     * @param ActiveStatus $status
     */
    protected function activationRequired(ActiveStatus $status): bool
    {
        if ($this->forceActivation) {
            $this->dayActivationStatus = 'activated: forced';
            return true;
        }
        if ($status->status !== 'activated') {
            $this->dayActivationStatus = "activated: was {$status->status}";
            return true;
        }
        if ($status->import_id === $this->import->id) {
            $this->dayActivationStatus = 'skipped: already activated for this set';
            return false;
        }
        if ($status->import && $status->import->md5 === $this->import->md5) {
            $this->dayActivationStatus = 'skipped: route sets are equal';
            return false;
        }
        if ($this->activateMissingOnly && $this->activeJourneys($status->id)) {
            $this->dayActivationStatus = 'skipped: data exists';
            return false;
        }
        $differ = $this->activationDiffer($status->id);
        $this->dayActivationStatus = $differ ? 'activated: modified' : 'skipped: not modified';
        return $differ;
    }

    /**
     * Build the active route data for a given date.
     *
     * @param string $date
     * @return $this
     */
    protected function buildActiveDate($date): self
    {
        // Reset internal overview of seen IDs
        $this->callIds = [];
        $this->aJourneyIds = [];
        $rawJourneys = self::getRawJourneys($date);
        $this->activateJourneys($date, $rawJourneys);
        $this->journeyDumper->flush();
        $this->callDumper->flush();
        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    protected function destroyActiveDate($date): self
    {
        DB::table('netex_active_calls', 'call')
            ->join('netex_active_journeys as journey', 'call.active_journey_id', '=', 'journey.id')
            ->whereDate('journey.date', $date)->delete();
        DB::table('netex_active_journeys')->whereDate('date', $date)->delete();
        return $this;
    }

    /**
     * True if core data differ from activated set for given date.
     *
     * @param string $dateStr
     * @return bool True if mismatch
     */
    protected function activationDiffer($dateStr): bool
    {
        return $this->diffDetector->differ($dateStr);
    }

    /**
     * @param string $date
     * @param Collection $rawJourneys
     */
    protected function activateJourneys($date, Collection $rawJourneys): void
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

    /**
     * @param mixed[] $jRec
     * @return void
     */
    protected function activateJourneyCalls(array &$jRec): void
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
    protected function activateCall(array $jRec, array $rawCall): void
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
     * Get number of activated journeys for given day.
     *
     * @param string $date
     */
    protected function activeJourneys($date): int
    {
        return ActiveJourney::where('date', $date)->count();
    }

    /**
     * Assert we have a uniform date string format.
     *
     * @param string|null $dateStr
     * @return string|null
     */
    protected function sanitizeDate($dateStr = null): string|null
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
    protected function invoke(Closure $callback = null): void
    {
        if (is_callable($callback)) {
            call_user_func_array($callback, array_slice(func_get_args(), 1));
        }
    }
}
