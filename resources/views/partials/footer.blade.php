<footer class="border-t border-stone-200 bg-white">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr] lg:px-6">
        <div>
            <x-logo />
            <p class="mt-3 max-w-sm text-sm leading-relaxed text-stone-500">
                {{ __('A tailor finder for Bandung: compare prices, read reviews, and contact tailors directly.') }}
            </p>
        </div>

        <nav aria-label="{{ __('Explore') }}">
            <h2 class="text-sm font-semibold text-stone-900">{{ __('Explore') }}</h2>
            <ul class="mt-3 space-y-2 text-sm text-stone-600">
                <li><a href="{{ route('dashboard') }}" class="hover:text-navy-700">{{ __('Tailor map') }}</a></li>
                <li><a href="{{ route('dashboard', ['dekat' => 1]) }}" class="hover:text-navy-700">{{ __('Find near me') }}</a></li>
                <li><a href="{{ route('dashboard', ['kategori' => 'kebaya']) }}" class="hover:text-navy-700">{{ __('Kebaya') }}</a></li>
                <li><a href="{{ route('dashboard', ['kategori' => 'permak']) }}" class="hover:text-navy-700">{{ __('Alterations') }}</a></li>
            </ul>
        </nav>

        <nav aria-label="{{ __('Account') }}">
            <h2 class="text-sm font-semibold text-stone-900">{{ __('Account') }}</h2>
            <ul class="mt-3 space-y-2 text-sm text-stone-600">
                @auth
                    <li><a href="{{ route('user.profile') }}" class="hover:text-navy-700">{{ __('Manage your account') }}</a></li>
                @else
                    <li><a href="{{ route('login') }}" class="hover:text-navy-700">{{ __('Log in') }}</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-navy-700">{{ __('Sign up') }}</a></li>
                @endauth
            </ul>
        </nav>
    </div>

    <div class="border-t border-dashed border-stitch">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-5 text-xs text-stone-400 sm:flex-row sm:items-center sm:justify-between lg:px-6">
            <p>&copy; {{ now()->year }} {{ config('app.name') }} · {{ __('Portfolio project') }}</p>
            <p>
                {{ __('Map data') }} &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener" class="hover:underline">OpenStreetMap</a>,
                <a href="https://openfreemap.org" target="_blank" rel="noopener" class="hover:underline">OpenFreeMap</a>
                · {{ __('Photos') }}: <a href="https://commons.wikimedia.org" target="_blank" rel="noopener" class="hover:underline">Wikimedia Commons</a>
            </p>
        </div>
    </div>
</footer>
