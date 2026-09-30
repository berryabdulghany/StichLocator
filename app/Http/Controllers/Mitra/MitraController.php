<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\TailorAccount;

/**
 * Dasar controller panel mitra: semua data diambil dari penjahit milik akun yang login,
 * tidak pernah dari ID di URL, jadi mitra tidak bisa mengubah penjahit lain.
 */
abstract class MitraController extends Controller
{
    protected function account(): TailorAccount
    {
        return auth('tailor')->user();
    }

    protected function location(): Location
    {
        return $this->account()->location;
    }
}
