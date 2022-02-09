<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\StopQuayAltId
 *
 * @property string $alt_id
 * @property string $stop_quay_id
 * @property-read \TromsFylkestrafikk\Netex\Models\StopQuay $stopQuay
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuayAltId newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuayAltId newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuayAltId query()
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuayAltId whereAltId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopQuayAltId whereStopQuayId($value)
 * @mixin \Eloquent
 */
class StopQuayAltId extends Model
{
    protected $table = 'netex_stop_quay_alt_id';
    protected $primaryKey = 'alt_id';
    protected $keyType = 'string';
    public $incrementing = false;

    public function stopQuay()
    {
        return $this->belongsTo(StopQuay::class);
    }
}
