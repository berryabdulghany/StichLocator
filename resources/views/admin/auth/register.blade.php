@extends('admin.auth.layout', ['title' => __('Create the first admin')])

@section('content')
    <h1 class="text-2xl font-bold text-stone-900">{{ __('Create the first admin') }}</h1>
    <p class="mt-1 text-sm text-stone-500">{{ __('This page is only available while no admin account exists. Add other admins from the Admins menu.') }}</p>

    @if ($errors->any())
        <div class="alert-error mt-6" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('admin.register.submit') }}" method="POST" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="name" class="label">{{ __('Name') }}</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" class="input py-2.5" autocomplete="name" required autofocus>
        </div>
        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="input py-2.5" autocomplete="username" required>
        </div>
        <div>
            <label for="password" class="label">{{ __('Password') }}</label>
            <input type="password" name="password" id="password" class="input py-2.5" autocomplete="new-password" minlength="8" required>
        </div>
        <div>
            <label for="password_confirmation" class="label">{{ __('Confirm password') }}</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="input py-2.5" autocomplete="new-password" minlength="8" required>
        </div>
        <button type="submit" class="btn-primary w-full py-2.5">{{ __('Create admin') }}</button>
    </form>
@endsection
