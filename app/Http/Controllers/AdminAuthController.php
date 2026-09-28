<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login');
    }

    public function showRegisterForm()
    {
        $this->ensureRegistrationOpen();

        return view('admin.register');
    }

    public function register(Request $request)
    {
        $this->ensureRegistrationOpen();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::guard('admin')->login($admin);

        return redirect()->route('admin.dashboard');
    }

    /**
     * Registrasi admin publik hanya untuk membuat admin pertama.
     * Setelah ada admin, form ini ditutup agar orang lain tidak bisa mendaftar sebagai admin.
     */
    public static function registrationOpen(): bool
    {
        return ! Admin::exists();
    }

    private function ensureRegistrationOpen(): void
    {
        abort_unless(self::registrationOpen(), 403, 'Registrasi admin sudah ditutup.');
    }
}
