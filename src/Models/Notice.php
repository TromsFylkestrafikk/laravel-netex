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
 * @method static \Illuminate\Database\Eloquent\Builder|Notice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice query()
 * @method static \Illuminate\Database\Eloquent\Builder|Notice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notice wherePublicCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notice whereText($value)
 */
class Notice extends Model
{
    use HasFactory;

    protected $table = 'netex_notices';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
