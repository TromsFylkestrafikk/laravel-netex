<?php

use Illuminate\Support\Facades\Route;
use TromsFylkestrafikk\Netex\Http\Controllers\ActiveJourneyController;

Route::resource('active_journeys', ActiveJourneyController::class)->only('show');
