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
                $notice[$item->notice_obj_ref] = $item->notice->text;
            } else {
                $notice[$item->notice_obj_ref] .= PHP_EOL . $item->notice->text;
            }
        });
        return response($notice);
    }

    public function journeyNotices()
    {
        $notice = [];
        $result = NoticeAssignment::with('notice')->get();
        $result->each(function ($item) use (&$notice) {
            list(, $obj, $id) = explode(':', $item->notice_obj_ref);
            if ($obj === 'ServiceJourney') {
                if (empty($notice[$id])) {
                    $notice[$id] = $item->notice->text;
                } else {
                    $notice[$id] .= PHP_EOL . $item->notice->text;
                }
            }
        });
        return response($notice);
    }
}
