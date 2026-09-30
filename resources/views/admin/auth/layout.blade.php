{{-- Halaman masuk/daftar admin: kartu di tengah dengan latar navy bermotif jahitan --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
</head>
<body class="flex min-h-screen flex-col bg-navy-900">
    <div class="pointer-events-none fixed inset-4 rounded-3xl border-2 border-dashed border-white/10" aria-hidden="true"></div>

    <div class="relative flex items-center justify-between px-6 py-5">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-white">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-navy-800">
                <i class="ti ti-needle-thread" aria-hidden="true"></i>
            </span>
            <span class="font-bold">{{ config('app.name') }}</span>
            <span class="rounded-md bg-terra-500 px-1.5 py-0.5 text-[10px] font-bold uppercase">{{ $badge ?? 'Admin' }}</span>
        </a>
        <x-lang-switch />
    </div>

    <main class="relative flex flex-1 items-center justify-center px-4 pb-16">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
            @yield('content')
        </div>
    </main>
</body>
</html>
