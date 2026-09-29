@extends('layouts.auth', ['title' => __('Sign up')])

@section('content')
    <h1 class="text-3xl font-bold tracking-tight text-stone-900">{{ __('Create an account') }}</h1>
    <p class="mt-2 text-stone-500">{{ __('Join to review and save your favorite tailors.') }}</p>

    @if ($errors->any())
        <div class="alert-error mt-6" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('register.process') }}" method="POST" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="name" class="label">{{ __('Full name') }}</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" class="input py-2.5" autocomplete="name" required autofocus>
        </div>

        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="input py-2.5"
                   placeholder="nama@email.com" autocomplete="email" required>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="passwordInput" class="label">{{ __('Password') }}</label>
                <div class="relative">
                    <input type="password" name="password" id="passwordInput" class="input py-2.5 pr-10" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-stone-400 hover:text-stone-700"
                            data-target="passwordInput" onclick="togglePasswordVisibility(this)" aria-label="{{ __('Show password') }}">
                        <i class="ti ti-eye-off" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div>
                <label for="confirmPasswordInput" class="label">{{ __('Confirm password') }}</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="confirmPasswordInput" class="input py-2.5 pr-10" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-stone-400 hover:text-stone-700"
                            data-target="confirmPasswordInput" onclick="togglePasswordVisibility(this)" aria-label="{{ __('Show password') }}">
                        <i class="ti ti-eye-off" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
        <p class="-mt-3 text-xs text-stone-500">{{ __('At least 8 characters.') }}</p>

        <button type="submit" class="btn-primary w-full py-2.5">{{ __('Sign up') }}</button>
    </form>

    <p class="mt-8 text-sm text-stone-500">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="font-semibold text-navy-700 hover:underline">{{ __('Log in') }}</a>
    </p>
@endsection
