<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TromsFylkestrafikk\Netex\Scopes\ValidDateScope;

/**
 * \TromsFylkestrafikk\Netex\Models\StopPlace
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
 * @property int $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Netex\Models\StopPlaceAltId[] $altIds
 * @property-read int|null $alt_ids_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Netex\Models\GroupOfStopPlaces[] $groups
 * @property-read int|null $groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Netex\Models\StopQuay[] $quays
 * @property-read int|null $quays_count
 * @property-read \TromsFylkestrafikk\Netex\Models\TopographicPlace|null $topographicPlace
 * @method static Builder|StopPlace active()
 * @method static Builder|StopPlace newModelQuery()
 * @method static Builder|StopPlace newQuery()
 * @method static Builder|StopPlace query()
 * @method static Builder|StopPlace whereActive($value)
 * @method static Builder|StopPlace whereChanged($value)
 * @method static Builder|StopPlace whereCreated($value)
 * @method static Builder|StopPlace whereCreatedAt($value)
 * @method static Builder|StopPlace whereId($value)
 * @method static Builder|StopPlace whereLatitude($value)
 * @method static Builder|StopPlace whereLongitude($value)
 * @method static Builder|StopPlace whereName($value)
 * @method static Builder|StopPlace whereParentSiteRef($value)
 * @method static Builder|StopPlace whereStopPlaceType($value)
 * @method static Builder|StopPlace whereTopographicPlaceRef($value)
 * @method static Builder|StopPlace whereUpdatedAt($value)
 * @method static Builder|StopPlace whereValidFromDate($value)
 * @method static Builder|StopPlace whereValidToDate($value)
 * @method static Builder|StopPlace whereVersion($value)
 * @mixin \Eloquent
 */
class StopPlace extends Model
{
    public $incrementing = false;
    protected $table = 'netex_stop_place';
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ValidDateScope());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function quays()
    {
        return $this->hasMany(StopQuay::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function altIds()
    {
        return $this->hasMany(StopPlaceAltId::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function topographicPlace()
    {
        return $this->belongsTo(TopographicPlace::class, 'topographicPlaceRef');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(GroupOfStopPlaces::class, 'netex_stop_place_group_member');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tariffZones()
    {
        return $this->belongsToMany(TariffZone::class, 'netex_stop_tariff_zone');
    }

    public function scopeActive(Builder $query)
    {
        $query->where('active', true);
    }
}
