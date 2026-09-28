<?php

namespace App\Http\Controllers;

use App\Models\Location;

class DashboardController extends Controller
{
    public function index()
    {
        $locations = Location::withCount('reviews')->get()->each(function ($location) {
            // Status dihitung dari jam operasional saat ini (WIB), bukan dari kolom status
            $location->status = $location->isOpenNow() ? 'Buka' : 'Tutup';
        });

        return view('pages.dashboard', compact('locations'));
    }
}
