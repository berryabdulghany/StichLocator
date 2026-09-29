<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteController extends Controller
{
    /** Moda perjalanan yang didukung => profil OpenRouteService */
    private const PROFILES = [
        'driving' => 'driving-car',
        'walking' => 'foot-walking',
    ];

    /**
     * Pratinjau rute dari lokasi pengguna ke penjahit.
     *
     * Permintaan lewat server (bukan langsung dari browser) supaya API key
     * OpenRouteService tetap tersimpan di .env dan tidak terlihat pengguna.
     */
    public function show(Request $request)
    {
        $validated = $request->validate([
            // Dibatasi ke wilayah Indonesia supaya endpoint tidak dipakai untuk keperluan lain
            'from_lat' => 'required|numeric|between:-11,6',
            'from_lng' => 'required|numeric|between:95,141',
            'to_lat' => 'required|numeric|between:-11,6',
            'to_lng' => 'required|numeric|between:95,141',
            'mode' => 'nullable|in:' . implode(',', array_keys(self::PROFILES)),
        ]);

        $key = config('services.openrouteservice.key');

        if (blank($key)) {
            return response()->json(['message' => __('Route preview is not configured.')], 503);
        }

        $mode = $validated['mode'] ?? 'driving';
        $coords = array_map(fn ($value) => round((float) $value, 5), [
            $validated['from_lng'], $validated['from_lat'], $validated['to_lng'], $validated['to_lat'],
        ]);

        // Rute yang sama (dibulatkan ~1 meter) disimpan 1 jam untuk menghemat kuota harian
        $cacheKey = 'route:' . $mode . ':' . implode(',', $coords);

        $route = Cache::get($cacheKey);

        if (! $route) {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => $key, 'Accept' => 'application/geo+json'])
                ->get(rtrim(config('services.openrouteservice.url'), '/') . '/v2/directions/' . self::PROFILES[$mode], [
                    'start' => $coords[0] . ',' . $coords[1],
                    'end' => $coords[2] . ',' . $coords[3],
                ]);

            $feature = $response->json('features.0');

            if ($response->failed() || ! $feature) {
                Log::warning('OpenRouteService gagal', ['status' => $response->status(), 'body' => $response->body()]);

                return response()->json(['message' => __('Could not calculate the route. Please try again.')], 502);
            }

            $route = [
                'mode' => $mode,
                'distance' => (int) round($feature['properties']['summary']['distance'] ?? 0), // meter
                'duration' => (int) round($feature['properties']['summary']['duration'] ?? 0), // detik
                // GeoJSON memakai [lng, lat]; Leaflet butuh [lat, lng]
                'coordinates' => array_map(fn ($point) => [$point[1], $point[0]], $feature['geometry']['coordinates'] ?? []),
            ];

            Cache::put($cacheKey, $route, now()->addHour());
        }

        return response()->json($route);
    }
}
