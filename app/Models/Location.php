<?php

namespace App\Models;

use App\Enums\ReviewTag;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\Uploads;
use Illuminate\Support\Str;

class Location extends Model
{
    use HasFactory, Prunable, SoftDeletes;

    /** Lama data di tempat sampah sebelum dihapus permanen (php artisan model:prune) */
    public const TRASH_DAYS = 30;

    /** Zona waktu jam operasional penjahit */
    public const TIMEZONE = 'Asia/Jakarta';

    protected $fillable = [
        'name', 'slug', 'address', 'description', 'telepon', 'offers_home_visit', 'is_published', 'rating', 'review_count',
        'status', 'image_url', 'lat', 'lng', 'opening_hours', 'closed_until', 'closure_note',
    ];

    protected $casts = [
        'offers_home_visit' => 'boolean',
        'is_published' => 'boolean',
        'review_count' => 'integer',
        'lat' => 'float',
        'lng' => 'float',
        'closed_until' => 'date',
    ];

    protected $appends = ['cover_url'];

    protected static function booted(): void
    {
        // Slug dibuat sekali saat penjahit ditambahkan dan tidak berubah saat nama diganti,
        // supaya link yang sudah dibagikan tetap berfungsi.
        static::creating(function (Location $location) {
            $location->slug ??= static::uniqueSlug($location->name);
        });

        // File unggahan (sampul & galeri) baru dihapus saat data dihapus permanen,
        // supaya penjahit di tempat sampah masih bisa dipulihkan lengkap dengan fotonya.
        static::forceDeleting(function (Location $location) {
            $location->photos()->pluck('path')->push($location->image_url)->unique()->each(fn ($path) => Uploads::delete($path));
        });
    }

    /** Hanya penjahit berstatus terbit yang tampil di halaman publik */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /** Penjahit yang sudah lebih dari 30 hari di tempat sampah */
    public function prunable()
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(self::TRASH_DAYS));
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'penjahit';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi
    |--------------------------------------------------------------------------
    */

    public function reviews()
    {
        return $this->hasMany(Review::class, 'location_id');
    }

    public function services()
    {
        return $this->hasMany(LocationService::class)->orderBy('sort_order')->orderBy('price_from');
    }

    public function hours()
    {
        return $this->hasMany(LocationHour::class)->orderBy('day_of_week');
    }

    public function photos()
    {
        return $this->hasMany(LocationPhoto::class)->orderBy('sort_order');
    }

    /** Akun mitra penjahit (jika sudah diundang & bergabung) */
    public function account()
    {
        return $this->hasOne(TailorAccount::class);
    }

    public function invitations()
    {
        return $this->hasMany(TailorInvitation::class)->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Atribut turunan
    |--------------------------------------------------------------------------
    */

    /** URL foto sampul; mendukung URL penuh maupun path relatif di folder public */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => static::resolveImageUrl($this->image_url));
    }

    public static function resolveImageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://']) ? $path : asset($path);
    }

    /** Link WhatsApp (wa.me) dari nomor telepon, dengan pesan pembuka opsional */
    public function whatsappUrl(?string $message = null): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->telepon);

        if ($digits === '') {
            return null;
        }

        // Format Indonesia: 08xx -> 628xx
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return 'https://wa.me/' . $digits . ($message ? '?text=' . rawurlencode($message) : '');
    }

    /** Harga termurah dari semua layanan (Rupiah), null jika belum ada layanan */
    public function priceFrom(): ?int
    {
        $min = $this->services->min('price_from');

        return $min === null ? null : (int) $min;
    }

    /** Kategori layanan unik yang dimiliki penjahit ini */
    public function categories(): array
    {
        return $this->services->pluck('category')->unique()->values()->all();
    }

    /** Jumlah tiap tag ulasan, urut dari yang terbanyak: ['rapi' => 9, ...] */
    public function reviewTagCounts(): array
    {
        $counts = [];

        $reviews = $this->relationLoaded('reviews') ? $this->reviews : $this->reviews()->get();

        foreach ($reviews as $review) {
            foreach ($review->tags ?? [] as $tag) {
                if (ReviewTag::tryFrom($tag)) {
                    $counts[$tag] = ($counts[$tag] ?? 0) + 1;
                }
            }
        }

        arsort($counts);

        return $counts;
    }

    /*
    |--------------------------------------------------------------------------
    | Jam operasional
    |--------------------------------------------------------------------------
    */

    /**
     * Jadwal mingguan: [0 => ['opens_at' => '08:00', 'closes_at' => '17:00'], 1 => null (libur), ...]
     * day_of_week mengikuti Carbon (0 = Minggu). Jika belum ada data per hari,
     * jam lama di kolom opening_hours dipakai untuk semua hari.
     */
    public function weeklyHours(): array
    {
        $week = array_fill(0, 7, null);

        if ($this->exists && $this->hours->isNotEmpty()) {
            foreach ($this->hours as $hour) {
                if ($hour->opens_at && $hour->closes_at) {
                    $week[$hour->day_of_week] = ['opens_at' => $hour->opens_at, 'closes_at' => $hour->closes_at];
                }
            }

            return $week;
        }

        if ($this->opening_hours && preg_match('/^\s*(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})\s*$/', $this->opening_hours, $m)) {
            return array_fill(0, 7, ['opens_at' => $m[1], 'closes_at' => $m[2]]);
        }

        return $week;
    }

    /**
     * Status buka saat ini beserta jam berikutnya.
     *
     * @return array{open: bool, closes_at: ?string, opens_at: ?string, opens_in_days: ?int}
     */
    public function openingStatus(?Carbon $now = null): array
    {
        $now = ($now ?? now())->copy()->setTimezone(self::TIMEZONE);

        // Libur sementara mengalahkan jadwal mingguan
        if ($this->isTemporarilyClosed($now)) {
            return ['open' => false, 'closes_at' => null, 'opens_at' => null, 'opens_in_days' => null];
        }

        $week = $this->weeklyHours();
        $time = $now->format('H:i');
        $today = $now->dayOfWeek;
        $yesterday = ($today + 6) % 7;

        // Jam kemarin yang melewati tengah malam (misal 22:00 - 02:00) dan belum tutup
        $prev = $week[$yesterday];
        if ($prev && $prev['closes_at'] < $prev['opens_at'] && $time < $prev['closes_at']) {
            return ['open' => true, 'closes_at' => $prev['closes_at'], 'opens_at' => null, 'opens_in_days' => null];
        }

        $cur = $week[$today];
        if ($cur) {
            $overnight = $cur['closes_at'] < $cur['opens_at'];
            $isOpen = $overnight
                ? $time >= $cur['opens_at']
                : $time >= $cur['opens_at'] && $time < $cur['closes_at'];

            if ($isOpen) {
                return ['open' => true, 'closes_at' => $cur['closes_at'], 'opens_at' => null, 'opens_in_days' => null];
            }

            if ($time < $cur['opens_at']) {
                return ['open' => false, 'closes_at' => null, 'opens_at' => $cur['opens_at'], 'opens_in_days' => 0];
            }
        }

        // Cari hari buka berikutnya (maksimal seminggu ke depan)
        for ($offset = 1; $offset <= 7; $offset++) {
            $day = $week[($today + $offset) % 7];
            if ($day) {
                return ['open' => false, 'closes_at' => null, 'opens_at' => $day['opens_at'], 'opens_in_days' => $offset];
            }
        }

        return ['open' => false, 'closes_at' => null, 'opens_at' => null, 'opens_in_days' => null];
    }

    /** Sedang libur sementara (sampai dan termasuk tanggal closed_until) */
    public function isTemporarilyClosed(?Carbon $now = null): bool
    {
        if (! $this->closed_until) {
            return false;
        }

        $today = ($now ?? now())->copy()->setTimezone(self::TIMEZONE)->toDateString();

        return $today <= $this->closed_until->toDateString();
    }

    public function isOpenNow(?Carbon $now = null): bool
    {
        return $this->openingStatus($now)['open'];
    }

    /** Teks status singkat: "Buka · tutup 20.00", "Tutup · buka besok 08.00" */
    public function statusText(?Carbon $now = null): string
    {
        if ($this->isTemporarilyClosed($now)) {
            return __('Temporarily closed until :date', ['date' => $this->closed_until->translatedFormat('j M')]);
        }

        $status = $this->openingStatus($now);
        $fmt = fn (string $time) => str_replace(':', '.', $time);

        if ($status['open']) {
            return __('Open') . ' · ' . __('closes :time', ['time' => $fmt($status['closes_at'])]);
        }

        if ($status['opens_at'] === null) {
            return __('Closed');
        }

        $when = match (true) {
            $status['opens_in_days'] === 0 => __('opens :time', ['time' => $fmt($status['opens_at'])]),
            $status['opens_in_days'] === 1 => __('opens tomorrow :time', ['time' => $fmt($status['opens_at'])]),
            default => __('opens :day :time', [
                'day' => ($now ?? now())->copy()->setTimezone(self::TIMEZONE)->addDays($status['opens_in_days'])->translatedFormat('l'),
                'time' => $fmt($status['opens_at']),
            ]),
        };

        return __('Closed') . ' · ' . $when;
    }

    /**
     * Hitung ulang rating rata-rata & jumlah ulasan dari tabel reviews.
     * Dipanggil setiap ulasan ditambah, diubah, atau dihapus.
     */
    public function refreshRatingStats(): void
    {
        $stats = $this->reviews()->selectRaw('AVG(rating) as average, COUNT(*) as total')->first();

        $this->forceFill([
            'rating' => $stats->total ? round((float) $stats->average, 1) : null,
            'review_count' => (int) $stats->total,
        ])->save();
    }

    /**
     * Samakan jam buka untuk semua hari (dipakai form admin yang masih satu rentang jam).
     */
    public function syncDailyHours(string $opensAt, string $closesAt): void
    {
        foreach (range(0, 6) as $day) {
            $this->hours()->updateOrCreate(
                ['day_of_week' => $day],
                ['opens_at' => $opensAt, 'closes_at' => $closesAt],
            );
        }

        $this->unsetRelation('hours');
    }

    /*
    |--------------------------------------------------------------------------
    | Data untuk halaman peta
    |--------------------------------------------------------------------------
    */

    /**
     * Wilayah (kecamatan) dari alamat berformat "Jalan, Kecamatan, Kota".
     * Contoh: "Jl. Buah Batu No. 45, Lengkong, Kota Bandung" -> "Lengkong".
     */
    public function area(): ?string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $this->address))));

        return count($parts) >= 3 ? $parts[count($parts) - 2] : null;
    }

    /** Pesan pembuka WhatsApp yang sudah terisi */
    public function whatsappGreeting(): string
    {
        return __('Hi :name, I found you on StichLocator. I would like to ask about your tailoring services.', [
            'name' => $this->name,
        ]);
    }

    /**
     * Ringkasan penjahit untuk katalog & marker peta.
     * Butuh relasi services dan hours, serta reviews_count (withCount).
     */
    public function toExplorerArray(): array
    {
        $priceFrom = $this->priceFrom();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'address' => $this->address,
            'area' => $this->area(),
            'lat' => $this->lat,
            'lng' => $this->lng,
            'cover_url' => $this->cover_url,
            'rating' => $this->rating !== null ? round((float) $this->rating, 1) : null,
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'is_open' => $this->isOpenNow(),
            'status_text' => $this->statusText(),
            'categories' => array_map(fn ($category) => $category->value, $this->categories()),
            'services' => $this->services->pluck('name')->implode(' '),
            'price_from' => $priceFrom,
            'price_from_text' => $priceFrom !== null ? \App\Support\Format::rupiahShort($priceFrom) : null,
            'home_visit' => (bool) $this->offers_home_visit,
            'detail_url' => route('penjahit.show', $this->slug),
            'whatsapp_url' => $this->whatsappUrl($this->whatsappGreeting()),
            'track_url' => route('penjahit.track', $this->id),
        ];
    }
}
