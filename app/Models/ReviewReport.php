<?php

namespace App\Models;

use App\Enums\ReportReason;
use Illuminate\Database\Eloquent\Model;

class ReviewReport extends Model
{
    protected $fillable = ['review_id', 'user_id', 'reason', 'note', 'resolved_at', 'resolved_by'];

    protected $casts = [
        'reason' => ReportReason::class,
        'resolved_at' => 'datetime',
    ];

    public function review()
    {
        return $this->belongsTo(Review::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }
}
