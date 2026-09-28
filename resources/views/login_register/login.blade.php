@extends('layouts.auth', ['title' => __('Log in')])

@section('content')
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-stone-900">{{ __('Welcome back') }}</h1>
        <p class="mt-1 text-sm text-stone-500">{{ __('Log in to find the best tailors near you.') }}</p>
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('login.process') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="input"
                   placeholder="nama@email.com" autocomplete="email" required autofocus>
        </div>

        <div>
            <label for="passwordInput" class="label">{{ __('Password') }}</label>
            <div class="relative">
                <input type="password" name="password" id="passwordInput" class="input pr-10"
                       autocomplete="current-password" required>
                <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-stone-400 hover:text-stone-700"
                        data-target="passwordInput" onclick="togglePasswordVisibility(this)" aria-label="{{ __('Show password') }}">
                    <i class="ti ti-eye-off" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary w-full">{{ __('Log in') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-500">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}" class="font-semibold text-navy-700 hover:underline">{{ __('Sign up') }}</a>
    </p>
@endsection
