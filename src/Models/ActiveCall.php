<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\ActiveCall
 *
 * @property int $id Unique ID of call for stop/journey/day/order
 * @property int $active_journey_id
 * @property int $line_private_code Internal numeric line number
 * @property string $destination Interim/current destination. Often changed during a journey
 * @property int $order Order of call during journey
 * @property string $quay_ref Stop place quay ID
 * @property string $stop_place_name Stop place name
 * @property bool $alighting Stop allows alighting
 * @property bool $boarding Stop allows boarding
 * @property string $call_time Arrival or departure iso datetime of call
 * @property string|null $arrival_time Full iso datetime of arrival
 * @property string|null $departure_time Full iso datetime of departure
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \TromsFylkestrafikk\Netex\Models\ActiveJourney|null $activeJourney
 * @property-read \TromsFylkestrafikk\Netex\Models\StopQuay|null $stopQuay
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereActiveJourneyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereAlighting($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereArrivalTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereBoarding($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereCallTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereDepartureTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereLinePrivateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereQuayRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereStopPlaceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveCall whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActiveCall extends Model
{
    use HasFactory;

    protected $table = 'netex_active_calls';
    protected $fillable = [
        'active_journey_id',
        'line_private_code',
        'destination',
        'order',
        'quay_ref',
        'stop_place_name',
        'alighting',
        'boarding',
        'call_time',
        'arrival_time',
        'departure_time',
    ];

    protected $casts = [
        'alighting' => 'boolean',
        'boarding' => 'boolean'
    ];

    public function activeJourney()
    {
        return $this->belongsTo(ActiveJourney::class);
    }

    public function stopQuay()
    {
        return $this->hasOne(StopQuay::class, 'quay_ref');
    }
}
