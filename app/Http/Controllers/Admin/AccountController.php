<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * "Akun saya": admin mengubah nama, email, dan password sendiri.
 */
class AccountController extends Controller
{
    public function edit()
    {
        return view('admin.account', ['admin' => Auth::guard('admin')->user()]);
    }

    public function update(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $validated = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('admins')->ignore($admin->id)],
        ]);

        $admin->update($validated);
        Activity::log('updated', $admin, 'Updated own account details');

        return back()->with('status', __('Profile updated.'));
    }

    /**
     * Ganti password: wajib password saat ini, lalu sesi di perangkat lain dikeluarkan.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        $admin = Auth::guard('admin')->user();
        $admin->update(['password' => $validated['password']]); // di-hash oleh cast 'hashed'

        // Keluarkan sesi admin ini di perangkat lain (misalnya jika password lama bocor)
        Auth::guard('admin')->logoutOtherDevices($validated['password']);

        Activity::log('password', $admin, 'Changed own password');

        return back()->with('status', __('Password updated.'));
    }
}
