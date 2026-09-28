<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    /** Zona waktu jam operasional penjahit */
    public const TIMEZONE = 'Asia/Jakarta';

    protected $fillable = [
        'name', 'address', 'telepon','rating', 'reviews', 'status', 'image_url', 'lat', 'lng', 'opening_hours'
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class, 'location_id');
    }

    /**
     * Cek apakah penjahit sedang buka berdasarkan opening_hours ("08:00 - 17:00").
     * Mendukung jam operasional yang melewati tengah malam (misal "22:00 - 02:00").
     */
    public function isOpenNow(?Carbon $now = null): bool
    {
        if (empty($this->opening_hours) || ! str_contains($this->opening_hours, '-')) {
            return false;
        }

        $now = ($now ?? now())->copy()->setTimezone(self::TIMEZONE);
        [$openStr, $closeStr] = array_map('trim', explode('-', $this->opening_hours, 2));

        try {
            $open = Carbon::createFromFormat('H:i', $openStr, self::TIMEZONE)->setDateFrom($now);
            $close = Carbon::createFromFormat('H:i', $closeStr, self::TIMEZONE)->setDateFrom($now);
        } catch (\Exception $e) {
            return false;
        }

        if ($close->lessThan($open)) {
            return $now->greaterThanOrEqualTo($open) || $now->lessThan($close);
        }

        return $now->between($open, $close, true);
    }
}
