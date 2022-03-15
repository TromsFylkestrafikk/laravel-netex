<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveJourney extends Model
{
    use HasFactory;

    protected $table = 'netex_active_journeys';
    protected $fillable = [
        'date',
        'journey_ref',
        'name',
        'private_code',
        'direction',
        'operator_ref',
        'line_private_code',
        'line_public_code',
        'transport_mode',
        'transport_submode',
        'first_stop_quay',
        'last_stop_quay',
        'timestamp_start',
        'timestamp_end',
    ];
}
