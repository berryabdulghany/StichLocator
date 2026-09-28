<header class="pointer-events-none absolute right-0 top-0 z-40 flex items-center gap-2 p-4">
    <x-lang-switch class="pointer-events-auto shadow-sm" />

    @auth
        <button id="profileButton" class="pointer-events-auto flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-navy-50 text-navy-700 shadow-md"
                aria-label="{{ __('Account') }}">
            @if(Auth::user()->profile_picture)
                <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ __('Profile picture') }}" class="h-full w-full object-cover">
            @else
                <i class="ti ti-user text-xl" aria-hidden="true"></i>
            @endif
        </button>
    @else
        <a href="{{ route('login') }}" class="btn-primary pointer-events-auto shadow-md">
            <i class="ti ti-login-2" aria-hidden="true"></i>{{ __('Log in') }}
        </a>
    @endauth
</header>
