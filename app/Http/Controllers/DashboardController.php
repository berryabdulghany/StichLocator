<?php

namespace App\Http\Controllers;

use App\Models\Location;

class DashboardController extends Controller
{
    public function index()
    {
        $locations = Location::withCount('reviews')->with('hours')->get()->each(function ($location) {
            // Status dihitung dari jam operasional saat ini (WIB), bukan dari kolom status
            $location->status = $location->isOpenNow() ? 'open' : 'closed';
        });

        return view('pages.dashboard', compact('locations'));
    }
}
