<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

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
