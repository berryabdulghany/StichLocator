<?php

namespace App\Http\Controllers\Mitra;

use App\Models\TailorAccount;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * "Akun saya" mitra: nama, email login, dan password.
 */
class AccountController extends MitraController
{
    public function edit()
    {
        return view('mitra.account', ['account' => $this->account()]);
    }

    public function update(Request $request)
    {
        $account = $this->account();

        $validated = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(TailorAccount::class)->ignore($account->id)],
        ]);

        $account->update($validated);
        Activity::log('updated', $account, 'Updated own account details');

        return back()->with('status', __('Profile updated.'));
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:tailor'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        $account = $this->account();
        $account->update(['password' => $validated['password']]);
        Auth::guard('tailor')->logoutOtherDevices($validated['password']);

        Activity::log('password', $account, 'Changed own password');

        return back()->with('status', __('Password updated.'));
    }
}
