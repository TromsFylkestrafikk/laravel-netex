<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TromsFylkestrafikk\Netex\Models\StopQuay;

/**
 * \TromsFylkestrafikk\Netex\Models\StopAssignment
 *
 * @method static \Illuminate\Database\Eloquent\Builder|StopAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopAssignment query()
 * @mixin \Eloquent
 * @property string $id
 * @property int $order
 * @property string $stop_point_ref
 * @property string $quay_ref
 * @method static \Illuminate\Database\Eloquent\Builder|StopAssignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopAssignment whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopAssignment whereQuayRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopAssignment whereStopPointRef($value)
 * @property-read StopQuay|null $quay
 */
class StopAssignment extends Model
{
    use HasFactory;

    protected $table = 'netex_stop_assignments';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quay()
    {
        return $this->belongsTo(StopQuay::class, 'quay_ref');
    }
}
