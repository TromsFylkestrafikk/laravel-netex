<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TromsFylkestrafikk\Netex\Models\ActiveStatus
 *
 * @property string $id Date of activation
 * @property int $import_id Reference to import ID
 * @property int|null $journeys Number of journeys this day
 * @property int|null $calls Number of calls for this day
 * @property string $status Activation status for given day
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \TromsFylkestrafikk\Netex\Models\Import|null $import
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveStatus whereCalls($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveStatus whereImportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveStatus whereJourneys($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveStatus whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActiveStatus whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActiveStatus extends Model
{
    public $incrementing = false;
    protected $table = 'netex_active_status';
    protected $keyType = 'string';
    protected $fillable = ['id', 'import_id', 'status', 'calls', 'journeys'];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
