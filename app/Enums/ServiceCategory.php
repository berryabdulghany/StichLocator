<?php

namespace App\Enums;

/**
 * Kategori layanan penjahit. Dipakai untuk bar kategori di halaman peta
 * dan tag layanan di kartu penjahit.
 */
enum ServiceCategory: string
{
    case Kebaya = 'kebaya';
    case Permak = 'permak';
    case Seragam = 'seragam';
    case Jas = 'jas';
    case Gaun = 'gaun';

    public function label(): string
    {
        return match ($this) {
            self::Kebaya => __('Kebaya'),
            self::Permak => __('Alterations'),
            self::Seragam => __('Uniforms'),
            self::Jas => __('Suits'),
            self::Gaun => __('Dresses'),
        };
    }

    /** Nama ikon Tabler (tanpa prefix "ti-") */
    public function icon(): string
    {
        return match ($this) {
            self::Kebaya => 'flower',
            self::Permak => 'scissors',
            self::Seragam => 'school',
            self::Jas => 'tie',
            self::Gaun => 'sparkles',
        };
    }
}
