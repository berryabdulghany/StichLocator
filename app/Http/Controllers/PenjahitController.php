<?php

namespace App\Http\Controllers;

use App\Enums\ReviewTag;
use App\Models\Location;
use App\Support\TailorStats;
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
        // Penjahit berstatus draf hanya bisa dipratinjau admin dan mitra pemiliknya
        $isOwner = auth('tailor')->user()?->location_id === $location->id;
        abort_unless($location->is_published || auth('admin')->check() || $isOwner, 404);

        $location->load(['services', 'hours', 'photos', 'reviews' => fn ($q) => $q->with('user')->latest()]);

        TailorStats::record($request, $location, 'views');

        $data = [
            'location' => $location,
            'reviews' => $location->getRelation('reviews'),
            'tagCounts' => $location->reviewTagCounts(),
            'reviewTags' => ReviewTag::cases(),
            'isOwner' => $isOwner,
        ];

        if ($request->ajax()) {
            return view('penjahit.partials.detail', $data);
        }

        return view('penjahit.show', $data);
    }
}
