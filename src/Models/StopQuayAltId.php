<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

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
