{{-- Layout halaman pengguna: header + konten. $fullHeight = true untuk halaman peta (tanpa scroll halaman). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    @stack('head')
</head>
<body @class(['flex h-dvh flex-col overflow-hidden' => $fullHeight ?? false, 'min-h-screen bg-stone-50' => ! ($fullHeight ?? false)])>
    <x-site-header>
        @yield('header-search')
    </x-site-header>

    @yield('content')

    @stack('scripts')
</body>
</html>
