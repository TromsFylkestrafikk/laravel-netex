<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\GroupOfStopPlaces
 *
 * @property string $id
 * @property string $version
 * @property string|null $created
 * @property string|null $changed
 * @property string $name
 * @property float $latitude
 * @property float $longitude
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Netex\Models\StopPlace[] $stopPlaces
 * @property-read int|null $stop_places_count
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces query()
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces whereChanged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GroupOfStopPlaces whereVersion($value)
 * @mixin \Eloquent
 */
class GroupOfStopPlaces extends Model
{
    protected $table = 'netex_group_of_stop_places';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function stopPlaces()
    {
        return $this->belongsToMany(StopPlace::class, 'netex_stop_place_group_member');
    }
}
