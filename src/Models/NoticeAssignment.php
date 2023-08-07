<?php

namespace TromsFylkestrafikk\Netex\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TromsFylkestrafikk\Netex\Models\Notice;

/**
 * \TromsFylkestrafikk\Netex\Models\NoticeAssignment
 *
 * @property string $id
 * @property string $notice_ref
 * @property string $notice_obj_ref
 * @property-read \TromsFylkestrafikk\Netex\Models\Notice|null $notice
 * @method static \Illuminate\Database\Eloquent\Builder|NoticeAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NoticeAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NoticeAssignment query()
 * @method static \Illuminate\Database\Eloquent\Builder|NoticeAssignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NoticeAssignment whereNoticeObjRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NoticeAssignment whereNoticeRef($value)
 */
class NoticeAssignment extends Model
{
    use HasFactory;

    protected $table = 'netex_notice_assignments';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function notice()
    {
        return $this->hasOne(Notice::class, 'id', 'notice_ref');
    }
}
