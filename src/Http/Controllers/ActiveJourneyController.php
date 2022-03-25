<?php

namespace TromsFylkestrafikk\Netex\Http\Controllers;

use Illuminate\Http\Request;
use TromsFylkestrafikk\Netex\Models\ActiveJourney;

class ActiveJourneyController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \TromsFylkestrafikk\Netex\Models\ActiveJourney  $activeJourney
     * @return \TromsFylkestrafikk\Netex\Models\ActiveJourney
     */
    public function show(ActiveJourney $activeJourney)
    {
        return $activeJourney;
    }
}
