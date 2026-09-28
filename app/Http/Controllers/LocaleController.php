<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Ganti bahasa aplikasi (ID | EN) lalu kembali ke halaman sebelumnya.
     */
    public function switch(Request $request, string $locale)
    {
        abort_unless(in_array($locale, config('app.supported_locales'), true), 404);

        $request->session()->put('locale', $locale);

        // Pakai header Referer (halaman yang benar-benar dilihat pengguna), bukan URL terakhir di session
        // yang bisa saja endpoint JSON. Hanya izinkan URL dari aplikasi ini sendiri.
        $referer = $request->headers->get('referer');
        $isInternal = $referer && str_starts_with($referer, $request->getSchemeAndHttpHost() . '/');

        return redirect()->to($isInternal ? $referer : route('dashboard'));
    }
}
