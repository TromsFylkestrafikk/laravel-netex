<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * \TromsFylkestrafikk\Netex\Models\Notice
 *
 * @property string $id
 * @property string $text
 * @property string $public_code
 */
class Notice extends Model
{
    use HasFactory;

    protected $table = 'netex_notices';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
