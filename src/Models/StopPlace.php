<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;
use TromsFylkestrafikk\Netex\Scopes\ValidDateScope;

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
