<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Location;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Pencatat log aktivitas admin.
 *
 * Contoh: Activity::log('updated', $tailor, 'Updated tailor :name', ['name' => $tailor->name]);
 * description memakai kunci terjemahan (bahasa Inggris) agar log tampil sesuai bahasa yang dipilih.
 */
class Activity
{
    private const SUBJECT_TYPES = [
        Location::class => 'tailor',
        Review::class => 'review',
        User::class => 'user',
        Admin::class => 'admin',
        ReviewReport::class => 'report',
    ];

    public static function log(string $action, ?Model $subject, string $description, array $properties = []): ActivityLog
    {
        return ActivityLog::create([
            'admin_id' => auth('admin')->id(),
            'action' => $action,
            'subject_type' => $subject ? (self::SUBJECT_TYPES[$subject::class] ?? class_basename($subject)) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties ?: null,
        ]);
    }
}
