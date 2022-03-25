<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * \TromsFylkestrafikk\Netex\Models\ActiveJourney
 *
 * @property string $id Unique ID for journey/day
 * @property string $date The date this journey belongs to. The actual journey is not necessary run on this day
 * @property string $vehicle_journey_id Journey ID
 * @property string $line_id Reference to netex_lines table
 * @property string $name Journey name
 * @property int $private_code Local journey code. Usually four digit code.
 * @property string $direction 'inbound' or 'outbound'
 * @property int $operator_id
 * @property int $line_private_code Internal numeric line number
 * @property string $line_public_code Line number as shown to the public
 * @property string $transport_mode 'bus', 'water', 'rail' or similar
 * @property string $transport_submode Detailed type of transport mode
 * @property string|null $first_stop_quay_id
 * @property string|null $last_stop_quay_id
 * @property string|null $start_at Departure time from first stop
 * @property string|null $end_at Arrival time on last stop
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Netex\Models\ActiveCall[] $activeCalls
 * @property-read int|null $active_calls_count
 * @property-read \TromsFylkestrafikk\Netex\Models\StopQuay|null $firstStopQuay
 * @property-read \TromsFylkestrafikk\Netex\Models\StopQuay|null $lastStopQuay
 * @property-read \TromsFylkestrafikk\Netex\Models\Line|null $line
 * @property-read \TromsFylkestrafikk\Netex\Models\Operator|null $operator
 * @property-read \TromsFylkestrafikk\Netex\Models\VehicleJourney|null $vehicleJourney
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereFirstStopQuayId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereLastStopQuayId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereLineId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereLinePrivateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereLinePublicCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereOperatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney wherePrivateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereTransportMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereTransportSubmode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereVehicleJourneyId($value)
 * @mixin \Eloquent
 */
class ActiveJourney extends Model
{
    use HasFactory;

    /**
     * @inheritdoc
     */
    public $incrementing = false;

    /**
     * @inheritdoc
     */
    protected $table = 'netex_active_journeys';

    /**
     * @inheritdoc
     */
    protected $keyType = 'string';

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'date',
        'vehicle_journey_id',
        'line_id',
        'name',
        'private_code',
        'direction',
        'operator_id',
        'line_private_code',
        'line_public_code',
        'transport_mode',
        'transport_submode',
        'first_stop_quay_id',
        'last_stop_quay_id',
        'timestamp_start',
        'timestamp_end',
    ];

    public function vehicleJourney()
    {
        return $this->belongsTo(VehicleJourney::class);
    }

    public function line()
    {
        return $this->belongsTo(Line::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function firstStopQuay()
    {
        return $this->belongsTo(StopQuay::class, 'first_stop_quay_id');
    }

    public function lastStopQuay()
    {
        return $this->belongsTo(StopQuay::class, 'last_stop_quay_id');
    }

    public function activeCalls()
    {
        return $this->hasMany(ActiveCall::class);
    }
}
