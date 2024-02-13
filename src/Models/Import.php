<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TromsFylkestrafikk\Netex\Models\Import
 *
 * @property int $id Incremental import ID.
 * @property string $path Full path to raw XML route set.
 * @property string|null $md5 MD5 sum of entire set
 * @property string|null $version Version attached to route set, if present
 * @property int $size Collected size of XMLs in route set
 * @property int $files Number of XMLs in route set
 * @property string|null $available_from Route set vailability from date
 * @property string|null $available_to Route set availability to date
 * @property string $import_status Status of this import
 * @property string|null $message Message of what failed during import
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \TromsFylkestrafikk\Netex\Models\ActiveStatus> $activStates
 * @property-read int|null $activ_states_count
 * @method static Builder|Import imported()
 * @method static Builder|Import newModelQuery()
 * @method static Builder|Import newQuery()
 * @method static Builder|Import query()
 * @method static Builder|Import whereAvailableFrom($value)
 * @method static Builder|Import whereAvailableTo($value)
 * @method static Builder|Import whereCreatedAt($value)
 * @method static Builder|Import whereFiles($value)
 * @method static Builder|Import whereId($value)
 * @method static Builder|Import whereImportStatus($value)
 * @method static Builder|Import whereMd5($value)
 * @method static Builder|Import whereMessage($value)
 * @method static Builder|Import wherePath($value)
 * @method static Builder|Import whereSize($value)
 * @method static Builder|Import whereUpdatedAt($value)
 * @method static Builder|Import whereVersion($value)
 * @mixin \Eloquent
 */
class Import extends Model
{
    protected $table = 'netex_imports';

    protected $fillable = [
        'path',
        'md5',
        'version',
        'size',
        'files',
        'available_from',
        'available_to',
        'import_status',
        'message',
    ];

    public function activStates(): HasMany
    {
        return $this->hasMany(ActiveStatus::class);
    }

    public function scopeImported(Builder $query): void
    {
        $query->where('import_status', 'imported');
    }
}
