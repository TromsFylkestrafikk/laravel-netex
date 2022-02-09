<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\StopPlaceAltId
 *
 * @property string $alt_id
 * @property string $stop_place_id
 * @property-read \TromsFylkestrafikk\Netex\Models\StopPlace $stopQuay
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlaceAltId newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlaceAltId newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlaceAltId query()
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlaceAltId whereAltId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopPlaceAltId whereStopPlaceId($value)
 * @mixin \Eloquent
 */
class StopPlaceAltId extends Model
{
    protected $table = 'netex_stop_place_alt_id';
    protected $primaryKey = 'alt_id';
    protected $keyType = 'string';
    public $incrementing = false;

    public function stopQuay()
    {
        return $this->belongsTo(StopPlace::class);
    }
}
