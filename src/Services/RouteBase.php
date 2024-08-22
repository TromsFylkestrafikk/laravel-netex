<?php

namespace TromsFylkestrafikk\Netex\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TromsFylkestrafikk\Netex\Models\ActiveCall;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;

class RouteBase
{
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
     * @param mixed[] $journeyRecord
     *
     * @return string
     */
    protected static function makeJourneyId(array $journeyRecord): string
    {
        return implode(':', [
            static::getCodespace($journeyRecord['vehicle_journey_id']),
            $journeyRecord['date'],
            $journeyRecord['line_private_code'],
            $journeyRecord['private_code'],
        ]);
    }

    /**
     * @param mixed[] $callRecord
     * @param string|mixed[] $journeyRecord
     */
    protected static function makeCallId(array $callRecord, $journeyRecord): string
    {
        return (is_array($journeyRecord)
                ? self::makeJourneyId($journeyRecord)
                : $journeyRecord)
            . ':'
            . $callRecord['order'];
    }

    /**
     * Extract the codespace portion of a NeTEx identifier
     */
    protected static function getCodespace(string $netexId): string
    {
        return explode(':', $netexId)[0];
    }

    /**
     * Expand a time only ('HH:mm:ss') time format to full date timestamp.
     *
     * For calls that passes midnight, we need to keep track of the date and
     * update it when needed. This updated Carbon timestamp is returned.
     *
     * @param \stdClass $rawCall
     * @param string $property
     * @param Carbon $prevCallStamp
     *
     * @return Carbon
     */
    protected static function expandCallTime(&$rawCall, string $property, Carbon $prevCallStamp): Carbon
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

    /**
     * Query the raw netex data for a day's journey data
     *
     * @param string $date
     *
     * @return Collection
     */
    protected static function getRawJourneys(string $date)
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
     * @return Collection
     */
    protected static function getRawCalls(string $journeyRef): Collection
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
            ->join('netex_stop_assignments as stopass', 'patstop.stop_point_ref', '=', 'stopass.stop_point_ref')
            ->join('netex_stop_quay as quay', 'stopass.quay_ref', '=', 'quay.id')
            ->join('netex_stop_place as stop', 'quay.stop_place_id', '=', 'stop.id')
            ->where('ptime.vehicle_journey_ref', '=', $journeyRef)
            ->orderBy('patstop.order')
            ->get();
        return $ret;
    }
}
