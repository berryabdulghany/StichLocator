<?php

namespace App\Support;

use App\Models\Location;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Statistik sederhana untuk mitra penjahit: kunjungan halaman, klik WhatsApp, dan klik rute.
 *
 * Setiap pengunjung dihitung sekali per hari per metrik (dicatat di session), sehingga
 * membuka ulang drawer yang sama tidak menambah angka. Bot, admin, dan penjahit yang
 * melihat halamannya sendiri tidak dihitung. Tidak ada data pribadi yang disimpan.
 */
class TailorStats
{
    public const METRICS = [
        'views' => 'views',
        'whatsapp' => 'whatsapp_clicks',
        'route' => 'route_clicks',
    ];

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|headless|lighthouse/i';

    public static function record(Request $request, Location $location, string $metric): bool
    {
        $column = self::METRICS[$metric] ?? null;

        if (! $column || ! $location->is_published || self::shouldIgnore($request, $location)) {
            return false;
        }

        $today = now(Location::TIMEZONE)->toDateString();
        $sessionKey = "stats.{$today}.{$metric}.{$location->id}";

        if ($request->hasSession()) {
            if ($request->session()->has($sessionKey)) {
                return false;
            }
            $request->session()->put($sessionKey, true);
        }

        DB::table('location_daily_stats')->upsert(
            [['location_id' => $location->id, 'date' => $today, $column => 1]],
            ['location_id', 'date'],
            [$column => DB::raw("{$column} + 1")],
        );

        return true;
    }

    /**
     * Angka per hari untuk N hari terakhir (hari tanpa data tetap muncul dengan nilai 0).
     *
     * @return Collection<int, array{date: string, label: string, views: int, whatsapp: int, route: int}>
     */
    public static function timeline(Location $location, int $days = 30): Collection
    {
        $end = now(Location::TIMEZONE)->startOfDay();
        $start = $end->copy()->subDays($days - 1);

        $rows = DB::table('location_daily_stats')
            ->where('location_id', $location->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($row) => substr($row->date, 0, 10));

        return collect(CarbonPeriod::create($start, $end))->map(function ($date) use ($rows) {
            $row = $rows[$date->toDateString()] ?? null;

            return [
                'date' => $date->toDateString(),
                'label' => $date->translatedFormat('j M'),
                'views' => (int) ($row->views ?? 0),
                'whatsapp' => (int) ($row->whatsapp_clicks ?? 0),
                'route' => (int) ($row->route_clicks ?? 0),
            ];
        })->values();
    }

    private static function shouldIgnore(Request $request, Location $location): bool
    {
        if (preg_match(self::BOT_PATTERN, (string) $request->userAgent())) {
            return true;
        }

        if (auth('admin')->check()) {
            return true;
        }

        return auth('tailor')->user()?->location_id === $location->id;
    }
}
