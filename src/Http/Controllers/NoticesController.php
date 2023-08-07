<?php

namespace TromsFylkestrafikk\Netex\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use TromsFylkestrafikk\Netex\Models\NoticeAssignment;

class NoticesController extends Controller
{
    /**
     * Return a multidimensional array of notices, keyed by the target's ID.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function allNotices()
    {
        $notice = [];
        $result = NoticeAssignment::with('notice')->get();
        $result->each(function ($item) use (&$notice) {
            // Since each target object can have multiple notices, we'll use a
            // multidimensional array ($notice) for storage.
            $notice[$item->notice_obj_ref][] = $item->notice->text;
        });
        return response($notice);
    }
}
