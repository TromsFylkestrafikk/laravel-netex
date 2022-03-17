<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\ActiveJourney
 *
 * @property int $id Unique ID for journey/day
 * @property string $date The date this journey belongs to. The actual journey is not necessary run on this day
 * @property string $journey_ref Journey identifier
 * @property string $name Journey name
 * @property int $private_code Local journey code. Usually four digit code.
 * @property string $direction 'inbound' or 'outbound'
 * @property int $operator_ref
 * @property int $line_private_code Internal numeric line number
 * @property string $line_public_code Line number as shown to the public
 * @property string $transport_mode 'bus', 'water', 'rail' or similar
 * @property string $transport_submode Detailed type of transport mode
 * @property string|null $first_stop_quay_ref
 * @property string|null $last_stop_quay_ref
 * @property string|null $start_at Departure time from first stop
 * @property string|null $end_at Arrival time on last stop
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \TromsFylkestrafikk\Netex\Models\StopQuay|null $firstStopQuay
 * @property-read \TromsFylkestrafikk\Netex\Models\StopQuay|null $lastStopQuay
 * @property-read \TromsFylkestrafikk\Netex\Models\Operator|null $operator
 * @property-read \TromsFylkestrafikk\Netex\Models\VehicleJourney|null $vehicleJourney
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereFirstStopQuayRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereJourneyRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereLastStopQuayRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereLinePrivateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereLinePublicCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereOperatorRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney wherePrivateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereTransportMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereTransportSubmode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveJourney whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActiveJourney extends Model
{
    use HasFactory;

    protected $table = 'netex_active_journeys';
    protected $fillable = [
        'date',
        'journey_ref',
        'name',
        'private_code',
        'direction',
        'operator_ref',
        'line_private_code',
        'line_public_code',
        'transport_mode',
        'transport_submode',
        'first_stop_quay_ref',
        'last_stop_quay_ref',
        'timestamp_start',
        'timestamp_end',
    ];

    public function vehicleJourney()
    {
        return $this->hasOne(VehicleJourney::class, 'journey_ref');
    }

    public function operator()
    {
        return $this->hasOne(Operator::class, 'operator_ref');
    }

    public function firstStopQuay()
    {
        return $this->hasOne(StopQuay::class, 'first_stop_quay_ref');
    }

    public function lastStopQuay()
    {
        return $this->hasOne(StopQuay::class, 'last_stop_quay_ref');
    }
}
