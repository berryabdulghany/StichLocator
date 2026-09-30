<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\TailorAccount;
use App\Models\TailorInvitation;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Akun mitra penjahit dari sisi admin: undang, batalkan undangan, dan cabut akses.
 *
 * Tidak ada server email, jadi link undangan ditampilkan sekali ke admin untuk
 * disalin atau dikirim lewat WhatsApp ke penjahit. Jika penjahit sudah punya akun,
 * link yang sama berfungsi sebagai link atur ulang password.
 */
class TailorAccountController extends Controller
{
    public function invite(Request $request, Location $tailor)
    {
        $account = $tailor->account;

        $validated = $request->validateWithBag('invite', [
            'email' => [
                'required', 'string', 'email', 'max:255',
                // Email tidak boleh dipakai akun mitra penjahit lain
                Rule::unique(TailorAccount::class, 'email')->ignore($account?->id),
            ],
        ]);

        [$invitation, $token] = TailorInvitation::issue($tailor, $validated['email'], auth('admin')->user());

        Activity::log('invited', $tailor, $account ? 'Created a password reset link for :tailor' : 'Invited :email to manage :tailor', [
            'tailor' => $tailor->name,
            'email' => $invitation->email,
        ]);

        return back()
            ->with('invite_link', route('mitra.invitation.show', $token))
            ->with('status', $account ? __('Password reset link created.') : __('Invitation link created.'));
    }

    public function cancelInvite(Location $tailor)
    {
        $tailor->invitations()->whereNull('accepted_at')->delete();

        return back()->with('status', __('Invitation cancelled.'));
    }

    public function revoke(Location $tailor)
    {
        $account = $tailor->account;
        abort_unless($account, 404);

        // Menghapus akun juga mengakhiri sesi mitra (guard tidak menemukan akunnya lagi)
        $account->delete();
        $tailor->invitations()->whereNull('accepted_at')->delete();

        Activity::log('revoked', $tailor, 'Revoked partner access of :email for :tailor', [
            'tailor' => $tailor->name,
            'email' => $account->email,
        ]);

        return back()->with('status', __('Partner access revoked.'));
    }
}
