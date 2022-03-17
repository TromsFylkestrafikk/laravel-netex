<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\Operator
 *
 * @property int $id
 * @property string $name
 * @property string $legal_name
 * @property int $company_number
 * @method static \Illuminate\Database\Eloquent\Builder|Operator newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Operator newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Operator query()
 * @method static \Illuminate\Database\Eloquent\Builder|Operator whereCompanyNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Operator whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Operator whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Operator whereName($value)
 * @mixin \Eloquent
 */
class Operator extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $table = 'netex_operators';
}
