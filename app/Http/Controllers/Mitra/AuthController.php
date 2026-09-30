<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('mitra.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('tailor')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('Invalid email or password.')])->onlyInput('email');
        }

        $request->session()->regenerate();
        $account = Auth::guard('tailor')->user();
        $account->forceFill(['last_login_at' => now()])->save();
        Activity::log('login', $account, 'Logged in');

        return redirect()->intended(route('mitra.dashboard'));
    }

    public function logout(Request $request)
    {
        Activity::log('logout', Auth::guard('tailor')->user(), 'Logged out');
        Auth::guard('tailor')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('mitra.login')->with('status', __('You have been logged out.'));
    }
}
