@extends('admin.auth.layout', ['title' => __('Admin login')])

@section('content')
    <h1 class="text-2xl font-bold text-stone-900">{{ __('Admin login') }}</h1>
    <p class="mt-1 text-sm text-stone-500">{{ __('Manage tailors, reviews, and users.') }}</p>

    @if ($errors->any())
        <div class="alert-error mt-6" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('admin.login.submit') }}" method="POST" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="input py-2.5" autocomplete="username" required autofocus>
        </div>
        <div>
            <label for="password" class="label">{{ __('Password') }}</label>
            <input type="password" name="password" id="password" class="input py-2.5" autocomplete="current-password" required>
        </div>
        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-stone-300 text-navy-700 focus:ring-navy-600">
            {{ __('Remember me') }}
        </label>
        <button type="submit" class="btn-primary w-full py-2.5">{{ __('Log in') }}</button>
    </form>

    @if ($registrationOpen)
        <p class="mt-6 text-center text-sm text-stone-500">
            {{ __('No admin account exists yet.') }}
            <a href="{{ route('admin.register') }}" class="font-semibold text-navy-700 hover:underline">{{ __('Create the first admin') }}</a>
        </p>
    @endif
@endsection
