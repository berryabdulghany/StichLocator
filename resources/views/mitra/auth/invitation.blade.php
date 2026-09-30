@extends('admin.auth.layout', ['title' => $account ? __('Set a new password') : __('Join as a partner'), 'badge' => __('Partner')])

@section('content')
    @php($location = $invitation->location)

    <div class="flex items-center gap-3">
        <img src="{{ $location->cover_url }}" alt="" class="h-12 w-12 rounded-xl bg-navy-50 object-cover">
        <div class="min-w-0">
            <p class="text-xs uppercase tracking-wide text-stone-500">{{ $account ? __('Partner account') : __('Invitation') }}</p>
            <p class="truncate font-semibold text-stone-900">{{ $location->name }}</p>
        </div>
    </div>

    <h1 class="mt-6 text-2xl font-bold text-stone-900">{{ $account ? __('Set a new password') : __('Join as a partner') }}</h1>
    <p class="mt-1 text-sm text-stone-500">
        {{ $account
            ? __('Choose a new password for your partner account.')
            : __('Create a password to manage your page on StichLocator: update hours and prices, add photos, and reply to reviews.') }}
    </p>

    @if ($errors->any())
        <div class="alert-error mt-6" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('mitra.invitation.accept', $token) }}" method="POST" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input type="email" id="email" value="{{ $invitation->email }}" class="input bg-stone-50 py-2.5 text-stone-500" autocomplete="username" readonly>
        </div>
        <div>
            <label for="name" class="label">{{ __('Your name') }}</label>
            <input type="text" name="name" id="name" value="{{ old('name', $account?->name) }}" class="input py-2.5" autocomplete="name" required maxlength="255" autofocus>
        </div>
        <div>
            <label for="password" class="label">{{ __('Password') }}</label>
            <input type="password" name="password" id="password" class="input py-2.5" autocomplete="new-password" minlength="8" required>
            <p class="mt-1 text-xs text-stone-500">{{ __('At least 8 characters.') }}</p>
        </div>
        <div>
            <label for="password_confirmation" class="label">{{ __('Confirm password') }}</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="input py-2.5" autocomplete="new-password" minlength="8" required>
        </div>
        <button type="submit" class="btn-primary w-full py-2.5">
            {{ $account ? __('Save password') : __('Create account') }}
        </button>
    </form>
@endsection
