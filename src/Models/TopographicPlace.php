<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

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
