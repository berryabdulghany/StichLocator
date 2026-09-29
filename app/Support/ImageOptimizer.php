<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Perkecil & kompres foto unggahan sebelum disimpan (GD, tanpa library tambahan).
 *
 * Foto dari HP bisa 3–8 MB dan 4000px; di halaman peta cukup ~1600px.
 * Hasil disimpan sebagai WebP (kualitas 80) sehingga ukurannya biasanya < 300 KB.
 * Jika GD gagal membaca file, file asli disimpan apa adanya.
 */
class ImageOptimizer
{
    /**
     * @return string path relatif di disk "public", misalnya "tailors/abc.webp"
     */
    public static function store(UploadedFile $file, string $directory, int $maxSize = 1600, int $quality = 80): string
    {
        $image = self::load($file);

        if (! $image || ! function_exists('imagewebp')) {
            return $file->store($directory, 'public');
        }

        $image = self::fixOrientation($image, $file);
        $image = self::resize($image, $maxSize);

        ob_start();
        imagewebp($image, null, $quality);
        $data = ob_get_clean();
        imagedestroy($image);

        $path = trim($directory, '/') . '/' . Str::random(40) . '.webp';
        Storage::disk('public')->put($path, $data);

        return $path;
    }

    private static function load(UploadedFile $file): \GdImage|false|null
    {
        $path = $file->getRealPath();

        $image = match ($file->getMimeType()) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/gif' => @imagecreatefromgif($path),
            default => false,
        };

        if ($image) {
            // Pertahankan transparansi (PNG/WebP)
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
    }

    /** Foto HP sering tersimpan miring dengan penanda orientasi di EXIF */
    private static function fixOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
        $angle = match ((int) $orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle ? imagerotate($image, $angle, 0) : $image;
    }

    private static function resize(\GdImage $image, int $maxSize): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSize / max($width, $height));

        if ($scale >= 1) {
            return $image;
        }

        $newWidth = (int) round($width * $scale);
        $newHeight = (int) round($height * $scale);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }
}
