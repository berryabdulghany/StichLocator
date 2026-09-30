<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Support\TailorStats;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Menerima sinyal klik tombol WhatsApp / rute dari halaman publik (navigator.sendBeacon)
 * untuk statistik mitra penjahit.
 */
class TrackController extends Controller
{
    public function store(Request $request, Location $location)
    {
        $validated = $request->validate([
            'metric' => ['required', Rule::in(['whatsapp', 'route'])],
        ]);

        TailorStats::record($request, $location, $validated['metric']);

        return response()->noContent();
    }
}
