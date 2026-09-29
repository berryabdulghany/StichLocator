<?php

namespace App\Http\Controllers;

use App\Enums\ServiceCategory;
use App\Models\Location;

class ExploreController extends Controller
{
    /**
     * Halaman peta + katalog penjahit. Data ringkas dikirim sekali ke JavaScript,
     * lalu pencarian, filter, dan urutan dilakukan di browser.
     */
    public function index()
    {
        $locations = Location::published()
            ->with(['services', 'hours'])
            ->withCount('reviews')
            ->get();

        $tailors = $locations->map->toExplorerArray()->values();

        $categories = collect(ServiceCategory::cases())->map(fn (ServiceCategory $category) => [
            'value' => $category->value,
            'label' => $category->label(),
            'icon' => $category->icon(),
            'count' => $tailors->filter(fn ($t) => in_array($category->value, $t['categories'], true))->count(),
        ]);

        $areas = $tailors->pluck('area')->filter()->unique()->sort()->values();

        return view('explore.index', compact('tailors', 'categories', 'areas'));
    }
}
