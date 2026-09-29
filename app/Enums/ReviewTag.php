<?php

namespace App\Enums;

/**
 * Tag cepat yang bisa dipilih saat menulis ulasan ("Rapi", "Tepat waktu", ...).
 * Jumlah tiap tag ditampilkan di detail penjahit.
 */
enum ReviewTag: string
{
    case Rapi = 'rapi';
    case TepatWaktu = 'tepat_waktu';
    case Ramah = 'ramah';
    case HargaPas = 'harga_pas';
    case SesuaiPesanan = 'sesuai_pesanan';

    public function label(): string
    {
        return match ($this) {
            self::Rapi => __('Neat work'),
            self::TepatWaktu => __('On time'),
            self::Ramah => __('Friendly'),
            self::HargaPas => __('Fair price'),
            self::SesuaiPesanan => __('As requested'),
        };
    }
}
