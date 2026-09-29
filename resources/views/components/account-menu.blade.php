{{-- Menu akun memakai <details> sehingga bisa dibuka/tutup tanpa JavaScript --}}
@auth
    <details class="relative" data-account-menu>
        <summary class="flex h-9 w-9 cursor-pointer list-none items-center justify-center overflow-hidden rounded-full border border-stone-300 bg-navy-50 text-navy-700 [&::-webkit-details-marker]:hidden"
                 aria-label="{{ __('Account') }}">
            @if (Auth::user()->profile_picture)
                <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="" class="h-full w-full object-cover">
            @else
                <i class="ti ti-user text-lg" aria-hidden="true"></i>
            @endif
        </summary>
        <div class="floating absolute right-0 mt-2 w-60 p-2 text-sm">
            <div class="border-b border-stone-100 px-3 pb-2 pt-1">
                <p class="font-semibold text-stone-800">{{ Auth::user()->name }}</p>
                <p class="truncate text-xs text-stone-500">{{ Auth::user()->email }}</p>
            </div>
            <a href="{{ route('user.profile') }}" class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-50">
                <i class="ti ti-settings" aria-hidden="true"></i>{{ __('Manage your account') }}
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-red-600 hover:bg-red-50">
                    <i class="ti ti-logout" aria-hidden="true"></i>{{ __('Log out') }}
                </button>
            </form>
        </div>
    </details>
@else
    <a href="{{ route('login') }}" class="btn-primary py-1.5">
        <i class="ti ti-login-2" aria-hidden="true"></i>{{ __('Log in') }}
    </a>
@endauth
