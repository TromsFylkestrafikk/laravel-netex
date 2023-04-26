<?php

namespace TromsFylkestrafikk\Netex\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Models\NoticeAssignment;

class NoticesController extends Controller
{
    public function allNotices()
    {
        $notice = [];
        $result = NoticeAssignment::with('notice')->get();
        $result->each(function ($item) use (&$notice) {
            if (empty($notice[$item->notice_obj_ref])) {
                $notice[$item->notice_obj_ref] = [$item->notice->text];
            } else {
                array_push($notice[$item->notice_obj_ref], $item->notice->text);
            }
        });
        return response($notice);
    }
}
