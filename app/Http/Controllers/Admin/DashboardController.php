<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceCategory;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    private const DAYS = 30;

    public function index()
    {
        $tailors = Location::with('hours')->get();
        $since = now()->subDays(self::DAYS - 1)->startOfDay();

        $stats = [
            'tailors' => $tailors->count(),
            'open_now' => $tailors->filter->isOpenNow()->count(),
            'reviews' => Review::count(),
            'reviews_recent' => Review::where('created_at', '>=', $since)->count(),
            'users' => User::count(),
            'users_recent' => User::where('created_at', '>=', $since)->count(),
            'rating' => round((float) Review::avg('rating'), 1),
        ];

        // Ulasan per hari selama 30 hari terakhir (hari tanpa ulasan tetap muncul dengan nilai 0)
        $perDay = Review::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $timeline = collect(CarbonPeriod::create($since, now()->startOfDay()))->map(fn ($date) => [
            'label' => $date->translatedFormat('j M'),
            'value' => (int) ($perDay[$date->toDateString()] ?? 0),
        ]);

        $distribution = collect(range(1, 5))->mapWithKeys(fn ($star) => [
            $star => Review::where('rating', $star)->count(),
        ]);

        $categories = collect(ServiceCategory::cases())->map(fn (ServiceCategory $category) => [
            'label' => $category->label(),
            'value' => LocationService::where('category', $category->value)->distinct('location_id')->count('location_id'),
        ])->sortByDesc('value')->values();

        $recentReviews = Review::with(['user', 'location'])->latest()->take(6)->get();

        // Penjahit dengan rating terendah (yang sudah punya ulasan) perlu dicek
        $needsAttention = Location::where('review_count', '>', 0)
            ->orderBy('rating')
            ->orderByDesc('review_count')
            ->take(4)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'timeline', 'distribution', 'categories', 'recentReviews', 'needsAttention',
        ));
    }
}
