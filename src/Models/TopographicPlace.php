<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\TopographicPlace
 *
 * @property string $id
 * @property string $version
 * @property string|null $created
 * @property string|null $changed
 * @property string $name
 * @property string|null $validFromDate
 * @property string|null $validToDate
 * @property string|null $isoCode
 * @property string $topographicPlaceType
 * @property string|null $parentTopographicPlaceref
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read TopographicPlace|null $parentTopographicPlace
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Netex\Models\StopPlace[] $stopPlaces
 * @property-read int|null $stop_places_count
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace query()
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereChanged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereIsoCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereParentTopographicPlaceref($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereTopographicPlaceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereValidFromDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereValidToDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopographicPlace whereVersion($value)
 * @mixin \Eloquent
 */
class TopographicPlace extends Model
{
    protected $table = 'netex_topographic_place';
    protected $keyType = 'string';
    public $incrementing = false;

    public function parentTopographicPlace()
    {
        return $this->belongsTo(TopographicPlace::class, 'parentTopographicPlaceref');
    }

    public function stopPlaces()
    {
        return $this->hasMany(StopPlace::class, 'topographicPlaceRef');
    }
}
