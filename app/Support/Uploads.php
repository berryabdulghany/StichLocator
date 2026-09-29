<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * File unggahan disimpan dengan awalan "storage/" (misalnya "storage/tailors/abc.webp"),
 * sedangkan foto demo berada di "images/...". Hanya file unggahan yang boleh dihapus.
 */
class Uploads
{
    public static function isUploaded(?string $path): bool
    {
        return $path !== null && Str::startsWith($path, 'storage/');
    }

    public static function delete(?string $path): void
    {
        if (self::isUploaded($path)) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }
}
