<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\PenjahitController;
use App\Http\Controllers\RouteController;

/*
|--------------------------------------------------------------------------
| Halaman publik
|--------------------------------------------------------------------------
*/
// Landing page
Route::get('/', [LandingController::class, 'index'])->name('home');

// Halaman peta + katalog penjahit (menerima filter awal: ?q=, ?kategori=, ?wilayah=, ?dekat=1)
Route::get('/peta', [ExploreController::class, 'index'])->name('dashboard');

// Detail penjahit (link yang bisa dibagikan), misalnya /penjahit/tailor-kebaya-bu-sri
Route::get('/penjahit/{location:slug}', [PenjahitController::class, 'show'])->name('penjahit.show');

// Pratinjau rute (proxy ke OpenRouteService, API key tetap di server)
Route::get('/rute', [RouteController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('route.preview');

// Ganti bahasa (ID | EN)
Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Data publik (read-only) untuk peta & detail penjahit
Route::get('/locations', [LocationController::class, 'getLocations'])->name('locations.get');
Route::get('/reviews/{location}', [ReviewController::class, 'getReviews'])
    ->whereNumber('location')
    ->name('reviews.location');

/*
|--------------------------------------------------------------------------
| Autentikasi pengguna
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1')
        ->name('register.process');
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.process');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/submit-review', [ReviewController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('submit-review');
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('user.profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('user.profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('user.password.update');

    // Pengguna menghapus ulasannya sendiri (dari halaman profil)
    Route::delete('/ulasan/{review}', [ReviewController::class, 'destroyOwn'])->name('reviews.destroy-own');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => auth('admin')->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('admin.login'))->name('home');

    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [Admin\AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [Admin\AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.submit');

        // Registrasi publik hanya untuk admin pertama (dicek di controller)
        Route::get('/register', [Admin\AuthController::class, 'showRegisterForm'])->name('register');
        Route::post('/register', [Admin\AuthController::class, 'register'])
            ->middleware('throttle:5,1')
            ->name('register.submit');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Penjahit: /admin/penjahit, /admin/penjahit/create, /admin/penjahit/{id}/edit, ...
        Route::resource('penjahit', Admin\TailorController::class)
            ->except('show')
            ->parameters(['penjahit' => 'tailor'])
            ->names('tailors');

        // Ulasan (moderasi)
        Route::get('/ulasan', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/ulasan/{review}/edit', [Admin\ReviewController::class, 'edit'])->name('reviews.edit');
        Route::put('/ulasan/{review}', [Admin\ReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/ulasan/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

        // Pengguna
        Route::get('/pengguna', [Admin\UserController::class, 'index'])->name('users.index');
        Route::delete('/pengguna/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

        // Akun admin
        Route::get('/admin', [Admin\AdminAccountController::class, 'index'])->name('admins.index');
        Route::post('/admin', [Admin\AdminAccountController::class, 'store'])->name('admins.store');
        Route::delete('/admin/{admin}', [Admin\AdminAccountController::class, 'destroy'])->name('admins.destroy');
    });
});
