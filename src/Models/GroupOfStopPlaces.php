<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

class GroupOfStopPlaces extends Model
{
    protected $table = 'netex_group_of_stop_places';
    public $incrementing = false;
    protected $keyType = 'string';

    public function stopPlaces()
    {
        return $this->belongsToMany(StopPlace::class, 'netex_stop_place_group_member');
    }
}
