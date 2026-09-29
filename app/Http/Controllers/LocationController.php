<?php

namespace App\Http\Controllers;

use App\Models\Location;

/**
 * Data penjahit publik (JSON). Pengelolaan penjahit oleh admin ada di Admin\TailorController.
 */
class LocationController extends Controller
{
    public function getLocations()
    {
        return response()->json(Location::all());
    }
}
