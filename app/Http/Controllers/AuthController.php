<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // Tampilkan halaman register
    public function showRegisterForm()
    {
        return view('login_register.register', ['quote' => $this->featuredReview()]);
    }

    // Proses register
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed'
        ]);

        // Simpan user ke database
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return redirect()->route('login')->with('success', __('Account created. Please log in.'));
    }

    // Tampilkan halaman login
    public function showLoginForm()
    {
        return view('login_register.login', ['quote' => $this->featuredReview()]);
    }

    // Proses login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))->with('success', __('Welcome back!'));
        }

        return back()
            ->withErrors(['email' => __('Invalid email or password.')])
            ->onlyInput('email');
    }

    /**
     * Halaman profil: ringkasan akun, ulasan saya, penjahit tersimpan, dan pengaturan.
     */
    public function showProfile()
    {
        $user = Auth::user();

        $reviews = $user->reviews()
            ->with('location')
            ->latest()
            ->get();

        $stats = [
            'reviews' => $reviews->count(),
            'average' => $reviews->count() ? round($reviews->avg('rating'), 1) : null,
            'tailors' => $reviews->pluck('location_id')->filter()->unique()->count(),
        ];

        return view('pages.profile', compact('user', 'reviews', 'stats'));
    }

    /**
     * Ubah nama dan foto profil.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:6144', // dikompres ke 400px
        ]);

        $user->name = $validated['name'];

        if ($request->hasFile('profile_picture')) {
            // Hapus foto lama jika ada
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $user->profile_picture = ImageOptimizer::store($request->file('profile_picture'), 'profile_pictures', 400);
        }

        $user->save();

        return redirect()->to(route('user.profile') . '#pengaturan')->with('status', __('Profile updated.'));
    }

    /**
     * Ubah password. Wajib memasukkan password saat ini supaya akun tidak bisa diambil alih
     * oleh orang yang kebetulan memegang perangkat yang sedang login.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return redirect()->to(route('user.profile') . '#pengaturan')->with('status', __('Password updated.'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home')->with('success', __('You have been logged out.'));
    }

    /**
     * Ulasan bintang 5 untuk ditampilkan di panel samping halaman login/register.
     */
    private function featuredReview(): ?Review
    {
        return Review::with(['user', 'location'])
            ->where('rating', 5)
            ->whereNotNull('user_id')
            ->whereHas('location', fn ($query) => $query->published())
            ->inRandomOrder()
            ->first();
    }
}
