<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\ImportStatus
 *
 * @property string $id
 * @property string $md5
 * @property int $size
 * @property int $files
 * @property string|null $date
 * @property string|null $valid_to
 * @property int|null $days
 * @property int|null $journeys
 * @property int|null $calls
 * @property int $status
 * @property bool|null $activated
 */
class Import extends Model
{
    protected $table = 'netex_import';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'md5',
        'size',
        'files',
        'date',
        'valid_to',
        'days',
        'journeys',
        'calls',
        'status',
        'activated',
    ];
}
