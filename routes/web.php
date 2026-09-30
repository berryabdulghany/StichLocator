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
use App\Http\Controllers\ReviewReportController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\Mitra;

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

// Statistik klik tombol WhatsApp / rute untuk mitra penjahit (dikirim lewat navigator.sendBeacon)
Route::post('/penjahit/{location}/klik', [TrackController::class, 'store'])
    ->whereNumber('location')
    ->middleware('throttle:60,1')
    ->name('penjahit.track');

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

    // Laporkan ulasan yang tidak pantas
    Route::post('/ulasan/{review}/laporkan', [ReviewReportController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('reviews.report');
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

        // Akun saya
        Route::get('/akun', [Admin\AccountController::class, 'edit'])->name('account.edit');
        Route::put('/akun', [Admin\AccountController::class, 'update'])->name('account.update');
        Route::put('/akun/password', [Admin\AccountController::class, 'updatePassword'])
            ->middleware('throttle:6,1')
            ->name('account.password');

        // Penjahit: /admin/penjahit, /admin/penjahit/create, /admin/penjahit/{id}/edit, ...
        Route::get('/penjahit/ekspor', [Admin\TailorController::class, 'export'])->name('tailors.export');
        Route::resource('penjahit', Admin\TailorController::class)
            ->except('show')
            ->parameters(['penjahit' => 'tailor'])
            ->names('tailors');

        // Akun mitra penjahit: undangan / link atur ulang password, dan cabut akses
        Route::post('/penjahit/{tailor}/undangan', [Admin\TailorAccountController::class, 'invite'])
            ->middleware('throttle:20,1')->name('tailors.invite');
        Route::delete('/penjahit/{tailor}/undangan', [Admin\TailorAccountController::class, 'cancelInvite'])->name('tailors.invite.cancel');
        Route::delete('/penjahit/{tailor}/akun', [Admin\TailorAccountController::class, 'revoke'])->name('tailors.account.revoke');

        // Ulasan (moderasi)
        Route::get('/ulasan', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/ulasan/ekspor', [Admin\ReviewController::class, 'export'])->name('reviews.export');
        Route::post('/ulasan/{review}/abaikan-laporan', [Admin\ReviewController::class, 'dismissReports'])->name('reviews.dismiss-reports');
        Route::get('/ulasan/{review}/edit', [Admin\ReviewController::class, 'edit'])->name('reviews.edit');
        Route::put('/ulasan/{review}', [Admin\ReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/ulasan/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

        // Pengguna
        Route::get('/pengguna', [Admin\UserController::class, 'index'])->name('users.index');
        Route::delete('/pengguna/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

        // Tempat sampah (data yang dihapus bisa dipulihkan selama 30 hari)
        Route::get('/sampah', [Admin\TrashController::class, 'index'])->name('trash.index');
        Route::post('/sampah/penjahit/{tailor}/pulihkan', [Admin\TrashController::class, 'restoreTailor'])
            ->withTrashed()->name('trash.tailors.restore');
        Route::delete('/sampah/penjahit/{tailor}', [Admin\TrashController::class, 'forceDeleteTailor'])
            ->withTrashed()->name('trash.tailors.destroy');
        Route::post('/sampah/ulasan/{review}/pulihkan', [Admin\TrashController::class, 'restoreReview'])
            ->withTrashed()->name('trash.reviews.restore');
        Route::delete('/sampah/ulasan/{review}', [Admin\TrashController::class, 'forceDeleteReview'])
            ->withTrashed()->name('trash.reviews.destroy');

        // Log aktivitas
        Route::get('/log', [Admin\ActivityLogController::class, 'index'])->name('activity.index');

        // Akun admin
        Route::get('/admin', [Admin\AdminAccountController::class, 'index'])->name('admins.index');
        Route::post('/admin', [Admin\AdminAccountController::class, 'store'])->name('admins.store');
        Route::delete('/admin/{admin}', [Admin\AdminAccountController::class, 'destroy'])->name('admins.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Mitra penjahit (/mitra)
|--------------------------------------------------------------------------
| Akun dibuat lewat link undangan dari admin. Semua halaman bekerja pada
| penjahit milik akun yang login, tidak ada ID penjahit di URL.
*/
Route::prefix('mitra')->name('mitra.')->group(function () {
    Route::get('/', fn () => auth('tailor')->check()
        ? redirect()->route('mitra.dashboard')
        : redirect()->route('mitra.login'))->name('home');

    Route::middleware('guest:tailor')->group(function () {
        Route::get('/login', [Mitra\AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [Mitra\AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.submit');
    });

    // Link undangan / atur ulang password dari admin
    Route::get('/undangan/{token}', [Mitra\InvitationController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('invitation.show');
    Route::post('/undangan/{token}', [Mitra\InvitationController::class, 'accept'])
        ->middleware('throttle:10,1')
        ->name('invitation.accept');

    Route::middleware(['auth:tailor', 'tailor.active'])->group(function () {
        Route::post('/logout', [Mitra\AuthController::class, 'logout'])->name('logout');

        Route::get('/dasbor', [Mitra\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/profil', [Mitra\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profil', [Mitra\ProfileController::class, 'update'])->name('profile.update');

        Route::post('/libur', [Mitra\ClosureController::class, 'update'])->name('closure.update');
        Route::delete('/libur', [Mitra\ClosureController::class, 'destroy'])->name('closure.destroy');

        Route::get('/ulasan', [Mitra\ReviewController::class, 'index'])->name('reviews.index');
        Route::put('/ulasan/{review}/balasan', [Mitra\ReviewController::class, 'reply'])
            ->middleware('throttle:30,1')
            ->name('reviews.reply');
        Route::delete('/ulasan/{review}/balasan', [Mitra\ReviewController::class, 'destroyReply'])->name('reviews.reply.destroy');

        Route::get('/akun', [Mitra\AccountController::class, 'edit'])->name('account.edit');
        Route::put('/akun', [Mitra\AccountController::class, 'update'])->name('account.update');
        Route::put('/akun/password', [Mitra\AccountController::class, 'updatePassword'])
            ->middleware('throttle:6,1')
            ->name('account.password');
    });
});
