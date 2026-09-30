<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Akun mitra penjahit (guard "tailor"). Satu akun terhubung ke satu penjahit.
 */
class TailorAccount extends Authenticatable
{
    protected $fillable = [
        'location_id',
        'name',
        'email',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    /** Penjahit yang dikelola; null jika penjahitnya sudah dipindahkan ke tempat sampah */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
