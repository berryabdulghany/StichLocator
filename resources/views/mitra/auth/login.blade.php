@extends('admin.auth.layout', ['title' => __('Partner login'), 'badge' => __('Partner')])

@section('content')
    <h1 class="text-2xl font-bold text-stone-900">{{ __('Partner login') }}</h1>
    <p class="mt-1 text-sm text-stone-500">{{ __('Manage your tailor page: hours, prices, photos, and reviews.') }}</p>

    @if (session('status'))
        <div class="alert-success mt-6" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error mt-6" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('mitra.login.submit') }}" method="POST" class="mt-6 space-y-5">
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

    <div class="mt-6 rounded-xl bg-stone-50 p-4 text-sm text-stone-600">
        <p class="font-semibold text-stone-800">{{ __('Own a tailor shop in Bandung?') }}</p>
        <p class="mt-1">{{ __('Partner accounts are created by invitation. Ask the StichLocator admin for an invitation link. Forgot your password? The admin can send you a reset link.') }}</p>
    </div>
@endsection
