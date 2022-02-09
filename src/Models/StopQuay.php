<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\StopQuay
 *
 * @property string $id
 * @property string $version
 * @property string|null $created
 * @property string|null $changed
 * @property string $stop_place_id
 * @property string $privateCode
 * @property string|null $publicCode
 * @property float $latitude
 * @property float $longitude
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\TromsFylkestrafikk\Netex\Models\StopQuayAltId[] $altIds
 * @property-read int|null $alt_ids_count
 * @property-read \TromsFylkestrafikk\Netex\Models\StopPlace $stopPlace
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay query()
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay whereChanged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay wherePrivateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay wherePublicCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay whereStopPlaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuay whereVersion($value)
 * @mixin \Eloquent
 */
class StopQuay extends Model
{
    protected $table = 'netex_stop_quay';
    protected $keyType = 'string';
    public $incrementing = false;

    public function stopPlace()
    {
        return $this->belongsTo(StopPlace::class);
    }

    public function altIds()
    {
        return $this->hasMany(StopQuayAltId::class);
    }
}
