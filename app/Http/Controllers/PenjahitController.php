<?php

namespace App\Http\Controllers;

use App\Enums\ReviewTag;
use App\Models\Location;
use Illuminate\Http\Request;

class PenjahitController extends Controller
{
    /**
     * Detail penjahit.
     * - Request AJAX dari halaman peta: potongan HTML untuk drawer.
     * - Request biasa (link yang dibagikan): halaman penuh.
     */
    public function show(Request $request, Location $location)
    {
        $location->load(['services', 'hours', 'photos', 'reviews' => fn ($q) => $q->with('user')->latest()]);

        $data = [
            'location' => $location,
            'reviews' => $location->getRelation('reviews'),
            'tagCounts' => $location->reviewTagCounts(),
            'reviewTags' => ReviewTag::cases(),
        ];

        if ($request->ajax()) {
            return view('penjahit.partials.detail', $data);
        }

        return view('penjahit.show', $data);
    }
}
