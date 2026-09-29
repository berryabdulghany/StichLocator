<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationHour extends Model
{
    protected $fillable = ['day_of_week', 'opens_at', 'closes_at'];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
