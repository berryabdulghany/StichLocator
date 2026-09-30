<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, Prunable, SoftDeletes;

    /** Lama data di tempat sampah sebelum dihapus permanen (php artisan model:prune) */
    public const TRASH_DAYS = 30;

    protected $fillable = [
        'user_id',
        'location_id',
        'rating',
        'review',
        'tags',
        'reply',
        'replied_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'replied_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function reports()
    {
        return $this->hasMany(ReviewReport::class);
    }

    /** Ulasan yang sudah lebih dari 30 hari di tempat sampah */
    public function prunable()
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(self::TRASH_DAYS));
    }
}
