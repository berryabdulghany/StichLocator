@extends('layouts.app')

@section('content')
<div class="h-full overflow-y-auto">
    <div class="mx-auto max-w-xl p-4 md:p-8">
        <div class="mb-4 flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="btn-icon" aria-label="{{ __('Back') }}">
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </a>
            <h2 class="text-2xl font-bold text-stone-900">{{ __('Edit profile') }}</h2>
        </div>

        <div class="card p-6">
            @if (session('status'))
                <div class="alert-success mb-4" role="alert">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert-error mb-4" role="alert">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('user.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="label">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name', Auth::user()->name) }}" class="input" required>
                </div>

                <div>
                    <label for="email" class="label">{{ __('Email') }}</label>
                    <input type="email" id="email" value="{{ Auth::user()->email }}" class="input bg-stone-50 text-stone-500" readonly>
                    <p class="mt-1 text-xs text-stone-500">{{ __('Email cannot be changed.') }}</p>
                </div>

                <div>
                    <label for="profile_picture" class="label">{{ __('Profile picture') }}</label>
                    <div class="flex items-center gap-4">
                        @if(Auth::user()->profile_picture)
                            <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ __('Profile picture') }}" class="h-20 w-20 rounded-full object-cover">
                        @else
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-navy-50 text-navy-700">
                                <i class="ti ti-user text-3xl" aria-hidden="true"></i>
                            </div>
                        @endif
                        <div>
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/*"
                                   class="block w-full text-sm text-stone-500 file:mr-3 file:rounded-full file:border-0 file:bg-navy-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-navy-700 hover:file:bg-navy-100">
                            <p class="mt-1 text-xs text-stone-500">{{ __('Max 2 MB. JPG, PNG, or GIF.') }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="password" class="label">{{ __('New password (optional)') }}</label>
                    <input type="password" name="password" id="password" class="input" autocomplete="new-password">
                </div>

                <div>
                    <label for="password_confirmation" class="label">{{ __('Confirm new password') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="input" autocomplete="new-password">
                </div>

                <button type="submit" class="btn-primary w-full">{{ __('Save changes') }}</button>
            </form>

            <form action="{{ route('logout') }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="btn-ghost w-full text-red-600 hover:bg-red-50">
                    <i class="ti ti-logout" aria-hidden="true"></i>{{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
