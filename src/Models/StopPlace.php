<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;
use TromsFylkestrafikk\Netex\Scopes\ValidDateScope;

/**
 * TromsFylkestrafikk\Netex\Models\StopPlace
 *
 * @property string $id
 * @property string $version
 * @property string|null $created
 * @property string|null $changed
 * @property string $name
 * @property string|null $stopPlaceType
 * @property float $latitude
 * @property float $longitude
 * @property string|null $validFromDate
 * @property string|null $validToDate
 * @property string|null $topographicPlaceRef
 * @property string|null $parentSiteRef
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Netex\Models\StopPlaceAltId[] $altIds
 * @property-read int|null $alt_ids_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Netex\Models\GroupOfStopPlaces[] $groups
 * @property-read int|null $groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Netex\Models\StopQuay[] $quays
 * @property-read int|null $quays_count
 * @property-read \TromsFylkestrafikk\Netex\Models\TopographicPlace|null $topographicPlace
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace query()
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereChanged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereParentSiteRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereStopPlaceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereTopographicPlaceRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereValidFromDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereValidToDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlace whereVersion($value)
 * @mixin \Eloquent
 */
class StopPlace extends Model
{
    protected $table = 'netex_stop_place';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ValidDateScope());
    }

    public function quays()
    {
        return $this->hasMany(StopQuay::class);
    }

    public function altIds()
    {
        return $this->hasMany(StopPlaceAltId::class);
    }

    public function topographicPlace()
    {
        return $this->belongsTo(TopographicPlace::class, 'topographicPlaceRef');
    }

    public function groups()
    {
        return $this->belongsToMany(GroupOfStopPlaces::class, 'netex_stop_place_group_member');
    }
}
