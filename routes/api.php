<?php

use Illuminate\Support\Facades\Route;
use TromsFylkestrafikk\Netex\Http\Controllers\NoticesController;

Route::get('notices', [NoticesController::class, 'allNotices']);
