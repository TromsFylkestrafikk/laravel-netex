<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveJourney extends Model
{
    use HasFactory;

    protected $table = 'netex_active_journeys';
}
