<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TromsFylkestrafikk\Netex\Scopes\ValidDateScope;

/**
 * TromsFylkestrafikk\Netex\Models\TariffZone
 *
 * @property string $id
 * @property string $version
 * @property string|null $created
 * @property string|null $changed
 * @property string $name
 * @property string|null $polygon_poslist
 * @property string|null $validFromDate
 * @property string|null $validToDate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone query()
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone whereChanged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone wherePolygonPoslist($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone whereValidFromDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone whereValidToDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TariffZone whereVersion($value)
 * @mixin \Eloquent
 */
class TariffZone extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $table = 'netex_tariff_zone';
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ValidDateScope());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function stopPlaces()
    {
        return $this->belongsToMany(StopPlace::class, 'netex_stop_tariff_zone');
    }
}
