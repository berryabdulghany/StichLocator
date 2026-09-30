<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mitra hanya bisa memakai panel selama data penjahitnya masih ada
 * (bukan di tempat sampah). Jika tidak, sesi mitra diakhiri.
 */
class EnsureTailorIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $account = Auth::guard('tailor')->user();

        if ($account && ! $account->location) {
            Auth::guard('tailor')->logout();

            return redirect()->route('mitra.login')
                ->withErrors(['email' => __('Your tailor listing is no longer active. Please contact the admin.')]);
        }

        return $next($request);
    }
}
