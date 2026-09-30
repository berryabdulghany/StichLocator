<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Bahasa (ID | EN) mengikuti pilihan pengguna di session
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'tailor.active' => \App\Http\Middleware\EnsureTailorIsActive::class,
        ]);

        // Tamu yang membuka halaman admin/mitra diarahkan ke halaman login masing-masing
        $middleware->redirectGuestsTo(fn (Request $request) => match (true) {
            $request->is('admin', 'admin/*') => route('admin.login'),
            $request->is('mitra', 'mitra/*') => route('mitra.login'),
            default => route('login'),
        });

        // Yang sudah login dan membuka halaman login/register diarahkan ke dasbor masing-masing
        $middleware->redirectUsersTo(fn (Request $request) => match (true) {
            $request->is('admin', 'admin/*') => route('admin.dashboard'),
            $request->is('mitra', 'mitra/*') => route('mitra.dashboard'),
            default => route('home'),
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
