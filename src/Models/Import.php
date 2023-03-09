<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\Import
 *
 * @property string $id Unique identifier for set.
 * @property string $path Path to raw XML set relative to netex disk.
 * @property string|null $md5 MD5 sum of entire set
 * @property string|null $available_from Route set vailability from date
 * @property string|null $available_to Route set availability to date
 * @property string $import_status Status of this import
 * @property string|null $message Message of what failed during import
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Import newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Import newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Import query()
 * @method static \Illuminate\Database\Eloquent\Builder|Import whereAvailableFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Import whereAvailableTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Import whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Import whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Import whereImportStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Import whereMd5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Import whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Import wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Import whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Import extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'netex_imports';
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'path',
        'md5',
        'available_from',
        'available_to',
        'import_status',
        'message',
    ];
}
