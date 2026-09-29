<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class LocationPhoto extends Model
{
    protected $fillable = ['path', 'credit', 'source_url', 'sort_order'];

    protected $appends = ['url'];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Location::resolveImageUrl($this->path));
    }
}
