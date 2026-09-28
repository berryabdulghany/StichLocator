<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminUserController;

/*
|--------------------------------------------------------------------------
| Halaman publik
|--------------------------------------------------------------------------
*/
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

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
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('/', function () {
        return auth('admin')->check()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('admin.login');
    });

    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
        Route::post('/login', [AdminAuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('admin.login.submit');
        // Registrasi admin hanya terbuka selama belum ada admin sama sekali (dicek di controller)
        Route::get('/register', [AdminAuthController::class, 'showRegisterForm'])->name('admin.register');
        Route::post('/register', [AdminAuthController::class, 'register'])
            ->middleware('throttle:5,1')
            ->name('admin.register.submit');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
        Route::get('/datapenjahit', [LocationController::class, 'index'])->name('datapenjahit');
        Route::get('/rating_review', [ReviewController::class, 'index'])->name('rating_review');

        // CRUD data penjahit
        Route::post('/penjahit', [LocationController::class, 'store'])->name('penjahit.store');
        Route::put('/penjahit/{id}', [LocationController::class, 'update'])->name('penjahit.update');
        Route::delete('/penjahit/{id}', [LocationController::class, 'destroy'])->name('penjahit.destroy');

        // Endpoint JSON untuk halaman Rating & Review
        Route::get('/api/locations', [LocationController::class, 'getLocations']);
        Route::get('/api/reviews', [ReviewController::class, 'index']);
        Route::post('/api/reviews', [ReviewController::class, 'store']);
        Route::get('/api/reviews/{id}', [ReviewController::class, 'show']);
        Route::put('/api/reviews/{id}', [ReviewController::class, 'update']);
        Route::delete('/api/reviews/{id}', [ReviewController::class, 'destroy']);
    });
});
