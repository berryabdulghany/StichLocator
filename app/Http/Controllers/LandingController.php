<?php

namespace App\Http\Controllers;

use App\Enums\ServiceCategory;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Review;
use App\Support\Format;

class LandingController extends Controller
{
    /**
     * Landing page: hero dengan pencarian, statistik, kategori, cara kerja,
     * dan penjahit dengan rating tertinggi. Semua angka diambil dari database.
     */
    public function index()
    {
        $locations = Location::published()->with(['services', 'hours'])->withCount('reviews')->get();
        $publishedReviews = Review::whereHas('location', fn ($query) => $query->published());

        $stats = [
            'tailors' => $locations->count(),
            'reviews' => (clone $publishedReviews)->count(),
            'rating' => round((float) (clone $publishedReviews)->avg('rating'), 1),
            'categories' => count(ServiceCategory::cases()),
        ];

        // Harga termurah & jumlah penjahit per kategori
        $minPrices = LocationService::whereIn('location_id', $locations->modelKeys())
            ->selectRaw('category, MIN(price_from) as min_price')
            ->groupBy('category')
            ->pluck('min_price', 'category');

        $categories = collect(ServiceCategory::cases())->map(fn (ServiceCategory $category) => [
            'value' => $category->value,
            'label' => $category->label(),
            'icon' => $category->icon(),
            'count' => $locations->filter(fn ($l) => in_array($category, $l->categories(), true))->count(),
            'price_from' => isset($minPrices[$category->value]) ? Format::rupiahShort((int) $minPrices[$category->value]) : null,
        ]);

        $topTailors = $locations
            ->sortByDesc(fn ($l) => [(float) $l->rating, $l->reviews_count])
            ->take(4)
            ->map->toExplorerArray()
            ->values();

        $areas = $locations->map->area()->filter()->unique()->sort()->values();

        // Penjahit untuk visual hero: paling banyak diulas (lalu rating) dengan minimal 3 layanan (supaya "nota" terisi)
        $heroTailor = $locations
            ->filter(fn ($l) => $l->services->count() >= 3)
            ->sortByDesc(fn ($l) => [$l->reviews_count, (float) $l->rating])
            ->first();

        return view('landing', compact('stats', 'categories', 'topTailors', 'areas', 'heroTailor'));
    }
}
