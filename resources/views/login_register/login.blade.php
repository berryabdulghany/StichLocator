@extends('layouts.auth', ['title' => __('Log in')])

@section('content')
    <h1 class="text-3xl font-bold tracking-tight text-stone-900">{{ __('Welcome back') }}</h1>
    <p class="mt-2 text-stone-500">{{ __('Log in to review and save your favorite tailors.') }}</p>

    @if (session('success'))
        <div class="alert-success mt-6">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error mt-6" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('login.process') }}" method="POST" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="input py-2.5"
                   placeholder="nama@email.com" autocomplete="email" required autofocus>
        </div>

        <div>
            <label for="passwordInput" class="label">{{ __('Password') }}</label>
            <div class="relative">
                <input type="password" name="password" id="passwordInput" class="input py-2.5 pr-10"
                       autocomplete="current-password" required>
                <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-stone-400 hover:text-stone-700"
                        data-target="passwordInput" onclick="togglePasswordVisibility(this)" aria-label="{{ __('Show password') }}">
                    <i class="ti ti-eye-off" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-stone-300 text-navy-700 focus:ring-navy-600">
            {{ __('Remember me') }}
        </label>

        <button type="submit" class="btn-primary w-full py-2.5">{{ __('Log in') }}</button>
    </form>

    <p class="mt-8 text-sm text-stone-500">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}" class="font-semibold text-navy-700 hover:underline">{{ __('Sign up') }}</a>
    </p>
@endsection
