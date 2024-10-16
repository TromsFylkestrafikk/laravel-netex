<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\VehicleJourney
 *
 * @property string $id
 * @property string $name
 * @property int $private_code
 * @property string $journey_pattern_ref
 * @property int $operator_ref
 * @property string $line_ref
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleJourney newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleJourney newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleJourney query()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleJourney whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleJourney whereJourneyPatternRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleJourney whereLineRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleJourney whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleJourney whereOperatorRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleJourney wherePrivateCode($value)
 * @mixin \Eloquent
 */
class VehicleJourney extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $table = 'netex_vehicle_journeys';
    protected $keyType = 'string';
}
