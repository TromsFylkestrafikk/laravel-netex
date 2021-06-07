<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

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
