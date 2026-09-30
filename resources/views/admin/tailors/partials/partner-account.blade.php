{{--
    Akun mitra penjahit di halaman edit penjahit (admin).
    Butuh: $tailor (dengan relasi account), $invitation (undangan yang masih berlaku atau null).
--}}
@php
    $account = $tailor->account;
    $inviteLink = session('invite_link');
    $shareText = $account
        ? __('Hi :name, here is the link to set a new password for your StichLocator partner account: :link', ['name' => $tailor->name, 'link' => $inviteLink])
        : __('Hi :name, you are invited to manage your page on StichLocator. Open this link to create your partner account: :link', ['name' => $tailor->name, 'link' => $inviteLink]);
@endphp

<section class="card mb-6 p-5" aria-labelledby="partner-account-title">
    <div class="flex flex-wrap items-start gap-4">
        <span @class([
            'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-xl',
            'bg-emerald-50 text-emerald-700' => $account,
            'bg-navy-50 text-navy-700' => ! $account,
        ])>
            <i class="ti {{ $account ? 'ti-user-check' : 'ti-user-plus' }}" aria-hidden="true"></i>
        </span>

        <div class="min-w-0 flex-1">
            <h2 id="partner-account-title" class="font-semibold text-stone-900">{{ __('Partner account') }}</h2>
            @if ($account)
                <p class="text-sm text-stone-600">
                    {{ $account->name }} · <span class="text-stone-500">{{ $account->email }}</span>
                </p>
                <p class="text-xs text-stone-500">
                    {{ $account->last_login_at ? __('Last login :time', ['time' => $account->last_login_at->diffForHumans()]) : __('Has not logged in yet') }}
                </p>
            @elseif ($invitation)
                <p class="text-sm text-stone-600">
                    {{ __('Invitation sent to :email, valid until :date.', ['email' => $invitation->email, 'date' => $invitation->expires_at->translatedFormat('j M Y, H:i')]) }}
                </p>
            @else
                <p class="text-sm text-stone-500">{{ __('Let the tailor update their own hours, prices, and photos, and reply to reviews.') }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($account)
                <form action="{{ route('admin.tailors.invite', $tailor) }}" method="POST">
                    @csrf
                    <input type="hidden" name="email" value="{{ $account->email }}">
                    <button type="submit" class="btn-outline px-3 py-1.5 text-sm">
                        <i class="ti ti-key" aria-hidden="true"></i>{{ __('Password reset link') }}
                    </button>
                </form>
                <form action="{{ route('admin.tailors.account.revoke', $tailor) }}" method="POST"
                      data-confirm="{{ __('Revoke partner access for :email? They will be logged out immediately.', ['email' => $account->email]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-ghost px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">
                        <i class="ti ti-user-x" aria-hidden="true"></i>{{ __('Revoke access') }}
                    </button>
                </form>
            @else
                <form action="{{ route('admin.tailors.invite', $tailor) }}" method="POST" class="flex flex-wrap gap-2">
                    @csrf
                    <label for="invite-email" class="sr-only">{{ __('Tailor email') }}</label>
                    <input type="email" name="email" id="invite-email" value="{{ old('email', $invitation?->email) }}" required maxlength="255"
                           class="input w-64 py-1.5" placeholder="{{ __('Tailor email') }}">
                    <button type="submit" class="btn-primary px-3 py-1.5 text-sm">
                        <i class="ti ti-link" aria-hidden="true"></i>{{ $invitation ? __('New link') : __('Create invitation') }}
                    </button>
                </form>
                @if ($invitation)
                    <form action="{{ route('admin.tailors.invite.cancel', $tailor) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-ghost px-3 py-1.5 text-sm">{{ __('Cancel invitation') }}</button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    @if ($errors->invite->any())
        <div class="alert-error mt-4" role="alert">{{ $errors->invite->first() }}</div>
    @endif

    @if ($inviteLink)
        {{-- Link hanya ditampilkan sekali (yang tersimpan di database hanya hash-nya) --}}
        <div class="mt-4 rounded-lg border border-dashed border-navy-300 bg-navy-50/60 p-4" data-invite-link>
            <p class="text-sm font-semibold text-navy-800">{{ __('Share this link with the tailor') }}</p>
            <p class="text-xs text-navy-700/80">{{ __('It is shown only once and is valid for :days days. Anyone with the link can set the password, so send it privately.', ['days' => \App\Models\TailorInvitation::VALID_DAYS]) }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                <input type="text" readonly value="{{ $inviteLink }}" class="input flex-1 font-mono text-xs" data-invite-input aria-label="{{ __('Invitation link') }}">
                <button type="button" class="btn-outline px-3 py-1.5 text-sm" data-copy-invite>
                    <i class="ti ti-copy" aria-hidden="true"></i><span data-copy-label>{{ __('Copy') }}</span>
                </button>
                @if ($wa = $tailor->whatsappUrl($shareText))
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn-accent px-3 py-1.5 text-sm">
                        <i class="ti ti-brand-whatsapp" aria-hidden="true"></i>{{ __('Send via WhatsApp') }}
                    </a>
                @endif
            </div>
        </div>
    @endif
</section>
