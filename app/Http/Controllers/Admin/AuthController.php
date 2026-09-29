<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login', ['registrationOpen' => self::registrationOpen()]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            Activity::log('login', Auth::guard('admin')->user(), 'Logged in');

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors(['email' => __('Invalid email or password.')])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Activity::log('logout', Auth::guard('admin')->user(), 'Logged out');
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Registrasi publik hanya untuk membuat admin pertama.
     * Admin berikutnya ditambahkan dari menu "Admin" di dalam panel.
     */
    public function showRegisterForm()
    {
        abort_unless(self::registrationOpen(), 403, __('Admin registration is closed.'));

        return view('admin.auth.register');
    }

    public function register(Request $request)
    {
        abort_unless(self::registrationOpen(), 403, __('Admin registration is closed.'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = Admin::create($validated); // password di-hash oleh cast 'hashed'
        Auth::guard('admin')->login($admin);

        return redirect()->route('admin.dashboard');
    }

    public static function registrationOpen(): bool
    {
        return ! Admin::exists();
    }
}
