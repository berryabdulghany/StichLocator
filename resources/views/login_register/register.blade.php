@extends('layouts.auth', ['title' => __('Sign up')])

@section('content')
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-stone-900">{{ __('Create an account') }}</h1>
        <p class="mt-1 text-sm text-stone-500">{{ __('Join to review and save your favorite tailors.') }}</p>
    </div>

    @if ($errors->any())
        <div class="alert-error mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('register.process') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="label">{{ __('Full name') }}</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" class="input" autocomplete="name" required autofocus>
        </div>

        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="input"
                   placeholder="nama@email.com" autocomplete="email" required>
        </div>

        <div>
            <label for="passwordInput" class="label">{{ __('Password') }}</label>
            <div class="relative">
                <input type="password" name="password" id="passwordInput" class="input pr-10" autocomplete="new-password" required>
                <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-stone-400 hover:text-stone-700"
                        data-target="passwordInput" onclick="togglePasswordVisibility(this)" aria-label="{{ __('Show password') }}">
                    <i class="ti ti-eye-off" aria-hidden="true"></i>
                </button>
            </div>
            <p class="mt-1 text-xs text-stone-500">{{ __('At least 8 characters.') }}</p>
        </div>

        <div>
            <label for="confirmPasswordInput" class="label">{{ __('Confirm password') }}</label>
            <div class="relative">
                <input type="password" name="password_confirmation" id="confirmPasswordInput" class="input pr-10" autocomplete="new-password" required>
                <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-stone-400 hover:text-stone-700"
                        data-target="confirmPasswordInput" onclick="togglePasswordVisibility(this)" aria-label="{{ __('Show password') }}">
                    <i class="ti ti-eye-off" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary w-full">{{ __('Sign up') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-500">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="font-semibold text-navy-700 hover:underline">{{ __('Log in') }}</a>
    </p>
@endsection
