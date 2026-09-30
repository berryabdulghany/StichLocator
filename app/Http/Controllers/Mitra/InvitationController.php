<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\TailorAccount;
use App\Models\TailorInvitation;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * Membuka link undangan dari admin: penjahit membuat password lalu langsung masuk.
 * Jika akunnya sudah ada, link ini dipakai untuk mengatur ulang password.
 */
class InvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = TailorInvitation::findPending($token);

        if (! $invitation || ! $invitation->location) {
            return response()->view('mitra.auth.invitation-invalid', [], 410);
        }

        return view('mitra.auth.invitation', [
            'invitation' => $invitation,
            'token' => $token,
            'account' => $invitation->location->account,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = TailorInvitation::findPending($token);

        if (! $invitation || ! $invitation->location) {
            return response()->view('mitra.auth.invitation-invalid', [], 410);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $location = $invitation->location;
        $existing = $location->account;

        // Email sempat dipakai akun mitra lain setelah undangan dibuat
        if (TailorAccount::where('email', $invitation->email)->where('location_id', '!=', $location->id)->exists()) {
            return back()->withErrors(['email' => __('This email is already used by another partner account. Ask the admin for a new invitation.')]);
        }

        $account = DB::transaction(function () use ($invitation, $location, $validated) {
            $account = TailorAccount::updateOrCreate(
                ['location_id' => $location->id],
                ['name' => $validated['name'], 'email' => $invitation->email, 'password' => $validated['password'], 'last_login_at' => now()],
            );
            $invitation->forceFill(['accepted_at' => now()])->save();

            return $account;
        });

        Auth::guard('tailor')->login($account);
        $request->session()->regenerate();

        Activity::log($existing ? 'password' : 'joined', $account, $existing ? 'Reset password via link' : 'Joined as partner for :tailor', [
            'tailor' => $location->name,
        ]);

        return redirect()->route('mitra.dashboard')->with('status', $existing
            ? __('Password updated.')
            : __('Welcome! You can now manage :name.', ['name' => $location->name]));
    }
}
