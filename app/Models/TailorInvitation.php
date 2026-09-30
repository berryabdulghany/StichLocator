<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Link undangan mitra penjahit dari admin. Dipakai juga sebagai link atur ulang password
 * jika penjahit sudah punya akun. Token asli hanya ditampilkan sekali ke admin;
 * yang disimpan di database hanya hash-nya.
 */
class TailorInvitation extends Model
{
    public const VALID_DAYS = 7;

    protected $fillable = ['location_id', 'email', 'token', 'expires_at', 'accepted_at', 'created_by'];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /** Undangan yang belum dipakai dan belum kedaluwarsa */
    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    /**
     * Buat undangan baru (undangan lama yang belum dipakai untuk penjahit ini dibatalkan).
     *
     * @return array{0: self, 1: string} undangan & token asli untuk dibagikan
     */
    public static function issue(Location $location, string $email, ?Admin $admin): array
    {
        static::where('location_id', $location->id)->whereNull('accepted_at')->delete();

        $token = Str::random(48);

        $invitation = static::create([
            'location_id' => $location->id,
            'email' => $email,
            'token' => static::hashToken($token),
            'expires_at' => now()->addDays(self::VALID_DAYS),
            'created_by' => $admin?->id,
        ]);

        return [$invitation, $token];
    }

    public static function findPending(string $token): ?self
    {
        return static::pending()->where('token', static::hashToken($token))->first();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
