<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\ImportStatus
 *
 * @property string $name
 * @property int $size
 * @property string $md5
 * @property string|null $import_date
 * @property string|null $valid_to
 * @property int $status
 * @property int $version
 */
class ImportStatus extends Model
{
    protected $table = 'netex_import';
    protected $primaryKey = 'name';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'name',
        'size',
        'md5',
        'import_date',
        'valid_to',
        'status',
        'version',
    ];
}
