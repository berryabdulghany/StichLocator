{{--
    Kerangka panel (admin & mitra penjahit): sidebar navy di kiri, bar atas berisi judul halaman.
    Diisi lewat $panel dari layouts.admin / layouts.mitra:
      badge, menu[], user (name, email), account_route, logout_route, external (url, label)
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => ($title ?? __('Dashboard')) . ' · ' . $panel['badge']])
    <meta name="robots" content="noindex">
    @stack('head')
</head>
<body class="min-h-screen bg-stone-50">
    {{-- Sidebar (HP: disembunyikan, dibuka lewat tombol menu) --}}
    <aside id="admin-sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy-900 text-navy-100 transition-transform lg:translate-x-0">
        <div class="flex h-16 items-center gap-2 border-b border-white/10 px-5">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-navy-800">
                <i class="ti ti-needle-thread" aria-hidden="true"></i>
            </span>
            <span class="font-bold text-white">{{ config('app.name') }}</span>
            <span class="ml-auto rounded-md bg-terra-500 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">{{ $panel['badge'] }}</span>
        </div>

        @isset($panel['context'])
            {{-- Nama usaha yang sedang dikelola (panel mitra) --}}
            <div class="border-b border-white/10 px-5 py-3">
                <p class="text-[11px] uppercase tracking-wide text-navy-100/60">{{ __('Managing') }}</p>
                <p class="truncate text-sm font-semibold text-white">{{ $panel['context'] }}</p>
            </div>
        @endisset

        <nav class="flex-1 space-y-1 p-3" aria-label="{{ __('Panel menu') }}">
            @foreach ($panel['menu'] as $item)
                @php($active = request()->routeIs($item['active']))
                <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                   @class([
                       'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                       'bg-white/10 text-white' => $active,
                       'text-navy-100/80 hover:bg-white/5 hover:text-white' => ! $active,
                   ])>
                    <i class="ti ti-{{ $item['icon'] }} text-lg" aria-hidden="true"></i>{{ $item['label'] }}
                    @if (! empty($item['badge']))
                        <span class="ml-auto rounded-full bg-terra-500 px-2 py-0.5 text-xs font-bold text-white"
                              title="{{ $item['badge_title'] ?? '' }}">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-3">
            <a href="{{ $panel['external']['url'] }}" target="_blank" rel="noopener"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-navy-100/80 hover:bg-white/5 hover:text-white">
                <i class="ti ti-external-link text-lg" aria-hidden="true"></i>{{ $panel['external']['label'] }}
            </a>
            <div class="mt-2 flex items-center gap-3 rounded-lg px-3 py-2">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white">
                    {{ mb_strtoupper(mb_substr($panel['user']->name, 0, 1)) }}
                </span>
                <a href="{{ route($panel['account_route']) }}" class="min-w-0 flex-1 rounded hover:underline" title="{{ __('My account') }}">
                    <p class="truncate text-sm font-semibold text-white">{{ $panel['user']->name }}</p>
                    <p class="truncate text-xs text-navy-100/70">{{ $panel['user']->email }}</p>
                </a>
                <form action="{{ route($panel['logout_route']) }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg p-1.5 text-navy-100/80 hover:bg-white/10 hover:text-white" title="{{ __('Log out') }}" aria-label="{{ __('Log out') }}">
                        <i class="ti ti-logout text-lg" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>
    <div id="admin-sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-stone-900/40 lg:hidden"></div>

    <div class="lg:pl-64">
        {{-- Bar atas --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-stone-200 bg-white/90 px-4 backdrop-blur lg:px-8">
            <button type="button" id="admin-sidebar-toggle" class="btn-icon lg:hidden" aria-label="{{ __('Open menu') }}" aria-controls="admin-sidebar" aria-expanded="false">
                <i class="ti ti-menu-2" aria-hidden="true"></i>
            </button>
            <h1 class="truncate text-lg font-semibold text-stone-900">{{ $title ?? __('Dashboard') }}</h1>
            <div class="ml-auto flex items-center gap-2">
                @yield('actions')
                <x-lang-switch />
            </div>
        </header>

        <main class="p-4 lg:p-8">
            @if (session('status'))
                <div class="alert-success mb-6 flex items-center gap-2" role="status">
                    <i class="ti ti-circle-check text-lg" aria-hidden="true"></i>{{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @vite('resources/js/admin.js')
    @stack('scripts')
</body>
</html>
