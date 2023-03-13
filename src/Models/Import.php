<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * TromsFylkestrafikk\Netex\Models\Import
 *
 * @property int $id Incremental import ID.
 * @property string $path Path to raw XML set relative to netex disk.
 * @property string|null $md5 MD5 sum of entire set
 * @property int $size Collected size of XMLs in route set
 * @property int $files Number of XMLs in route set
 * @property int|null $journeys Number of journeys found in set
 * @property int|null $calls Number of calls found in set
 * @property string|null $available_from Route set vailability from date
 * @property string|null $available_to Route set availability to date
 * @property int|null $activated Route set is activated
 * @property string $import_status Status of this import
 * @property string|null $message Message of what failed during import
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder|Import imported()
 * @method static Builder|Import newModelQuery()
 * @method static Builder|Import newQuery()
 * @method static Builder|Import query()
 * @method static Builder|Import whereActivated($value)
 * @method static Builder|Import whereAvailableFrom($value)
 * @method static Builder|Import whereAvailableTo($value)
 * @method static Builder|Import whereCalls($value)
 * @method static Builder|Import whereCreatedAt($value)
 * @method static Builder|Import whereFiles($value)
 * @method static Builder|Import whereId($value)
 * @method static Builder|Import whereImportStatus($value)
 * @method static Builder|Import whereJourneys($value)
 * @method static Builder|Import whereMd5($value)
 * @method static Builder|Import whereMessage($value)
 * @method static Builder|Import wherePath($value)
 * @method static Builder|Import whereSize($value)
 * @method static Builder|Import whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Import extends Model
{
    protected $table = 'netex_imports';

    protected $fillable = [
        'path',
        'md5',
        'size',
        'files',
        'journeys',
        'calls',
        'available_from',
        'available_to',
        'activated',
        'import_status',
        'message',
    ];

    public function scopeImported(Builder $query): void
    {
        $query->where('import_status', 'imported');
    }
}
