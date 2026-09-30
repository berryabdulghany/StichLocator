<?php

namespace App\Http\Controllers\Mitra;

use App\Support\TailorStats;

class DashboardController extends MitraController
{
    public function index()
    {
        $location = $this->location()->load(['hours', 'services'])->loadCount('photos');
        $timeline = TailorStats::timeline($location, 30);

        $totals = [
            'views' => $timeline->sum('views'),
            'whatsapp' => $timeline->sum('whatsapp'),
            'route' => $timeline->sum('route'),
        ];

        $unreplied = $location->reviews()->whereNull('reply')->with('user')->latest()->take(4)->get();
        $unrepliedCount = $location->reviews()->whereNull('reply')->count();

        // Kelengkapan profil: hal kecil yang membuat pelanggan lebih yakin
        $checklist = [
            ['done' => filled($location->description), 'label' => __('Add a short description'), 'hint' => __('Tell customers what you specialise in.')],
            ['done' => filled($location->telepon), 'label' => __('Add a WhatsApp number'), 'hint' => __('Customers contact you directly from the map.')],
            ['done' => $location->services->count() >= 2, 'label' => __('List at least 2 services with prices'), 'hint' => __('Prices help customers compare before visiting.')],
            ['done' => $location->photos_count >= 3, 'label' => __('Upload at least 3 photos'), 'hint' => __('Show your workshop and finished work.')],
        ];

        return view('mitra.dashboard', compact('location', 'timeline', 'totals', 'unreplied', 'unrepliedCount', 'checklist'));
    }
}
