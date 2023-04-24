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
 */
class NoticeAssignment extends Model
{
    use HasFactory;

    protected $table = 'netex_notice_assignments';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function notice()
    {
        return $this->hasOne(Notice::class, 'id', 'notice_ref');
    }
}
