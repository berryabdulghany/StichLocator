{{-- Layout panel admin: sidebar navy di kiri, bar atas berisi judul halaman --}}
@php
    $menu = [
        ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'label' => __('Dashboard')],
        ['route' => 'admin.tailors.index', 'active' => 'admin.tailors.*', 'icon' => 'needle-thread', 'label' => __('Tailors')],
        ['route' => 'admin.reviews.index', 'active' => 'admin.reviews.*', 'icon' => 'message-circle', 'label' => __('Reviews')],
        ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'users', 'label' => __('Users')],
        ['route' => 'admin.admins.index', 'active' => 'admin.admins.*', 'icon' => 'shield-lock', 'label' => __('Admins')],
    ];
    $admin = auth('admin')->user();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => ($title ?? __('Dashboard')) . ' · Admin'])
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
            <span class="ml-auto rounded-md bg-terra-500 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">Admin</span>
        </div>

        <nav class="flex-1 space-y-1 p-3" aria-label="{{ __('Admin menu') }}">
            @foreach ($menu as $item)
                @php($active = request()->routeIs($item['active']))
                <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                   @class([
                       'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                       'bg-white/10 text-white' => $active,
                       'text-navy-100/80 hover:bg-white/5 hover:text-white' => ! $active,
                   ])>
                    <i class="ti ti-{{ $item['icon'] }} text-lg" aria-hidden="true"></i>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-3">
            <a href="{{ route('home') }}" target="_blank" rel="noopener"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-navy-100/80 hover:bg-white/5 hover:text-white">
                <i class="ti ti-external-link text-lg" aria-hidden="true"></i>{{ __('View site') }}
            </a>
            <div class="mt-2 flex items-center gap-3 rounded-lg px-3 py-2">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white">
                    {{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ $admin->name }}</p>
                    <p class="truncate text-xs text-navy-100/70">{{ $admin->email }}</p>
                </div>
                <form action="{{ route('admin.logout') }}" method="POST">
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
