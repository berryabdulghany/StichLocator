<?php

namespace App\Models;

use App\Enums\ServiceCategory;
use Illuminate\Database\Eloquent\Model;

class LocationService extends Model
{
    protected $fillable = [
        'category', 'name', 'price_from', 'duration_min_days', 'duration_max_days', 'sort_order',
    ];

    protected $casts = [
        'category' => ServiceCategory::class,
        'price_from' => 'integer',
        'duration_min_days' => 'integer',
        'duration_max_days' => 'integer',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /** Estimasi pengerjaan, misalnya "5–7 hari" atau "1 hari" */
    public function durationText(): string
    {
        $range = $this->duration_min_days === $this->duration_max_days
            ? (string) $this->duration_min_days
            : $this->duration_min_days . '–' . $this->duration_max_days;

        return trans_choice(':range day|:range days', $this->duration_max_days, ['range' => $range]);
    }
}
