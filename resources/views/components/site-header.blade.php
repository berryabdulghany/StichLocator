@props([
    // true untuk halaman biasa: isi header disejajarkan dengan lebar konten dan menempel saat di-scroll.
    // false untuk halaman peta: header selebar layar.
    'contained' => false,
])

@php
    $onLanding = request()->routeIs('home');

    // Menu tengah: di landing page menggulir ke bagian halaman, di halaman lain cukup tautan ke peta
    $links = $onLanding
        ? [
            ['href' => route('dashboard'), 'label' => __('Tailor map'), 'icon' => 'map-2'],
            ['href' => '#kategori', 'label' => __('Categories')],
            ['href' => '#cara-kerja', 'label' => __('How it works')],
            ['href' => '#penjahit-teratas', 'label' => __('Top tailors')],
        ]
        : (request()->routeIs('dashboard') ? [] : [
            ['href' => route('dashboard'), 'label' => __('Tailor map'), 'icon' => 'map-2'],
        ]);
@endphp

<header @class([
    'z-[1200] border-b border-stone-200',
    'sticky top-0 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/80' => $contained,
    'relative bg-white' => ! $contained,
])>
    {{-- HP: flex (pencarian turun ke baris kedua). Desktop: grid 3 kolom dengan kolom kiri & kanan
         sama lebar (1fr), sehingga pencarian/menu di kolom tengah selalu tepat di tengah layar. --}}
    <div @class([
        'flex flex-wrap items-center gap-x-4 gap-y-2 py-2.5 lg:grid lg:grid-cols-[1fr_auto_1fr]',
        'mx-auto max-w-6xl px-4 lg:px-6' => $contained,
        'px-4 lg:px-6' => ! $contained,
    ])>
        <x-logo class="shrink-0 lg:justify-self-start" />

        {{-- Slot pencarian (hanya di halaman peta). Di layar kecil pindah ke baris kedua. --}}
        @if (trim($slot))
            <div class="order-last w-full lg:order-none lg:w-[34rem] xl:w-[40rem]">
                {{ $slot }}
            </div>
        @elseif ($links)
            <nav class="mx-auto hidden md:block lg:mx-0" aria-label="{{ __('Main menu') }}">
                <ul class="flex items-center gap-1">
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link['href'] }}"
                               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-stone-900">
                                @isset($link['icon'])
                                    <i class="ti ti-{{ $link['icon'] }} text-base" aria-hidden="true"></i>
                                @endisset
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @else
            {{-- Pengisi kolom tengah agar aksi tetap di kolom kanan --}}
            <div class="hidden lg:block" aria-hidden="true"></div>
        @endif

        {{-- Aksi di kanan atas: bahasa + akun/masuk --}}
        <div class="ml-auto flex shrink-0 items-center gap-2 lg:justify-self-end">
            @if ($links && ! trim($slot))
                {{-- Di HP menu tengah disembunyikan; tautan peta tetap ada sebagai ikon --}}
                <a href="{{ route('dashboard') }}" class="btn-icon md:hidden" aria-label="{{ __('Tailor map') }}">
                    <i class="ti ti-map-2" aria-hidden="true"></i>
                </a>
            @endif
            <x-lang-switch />
            <x-account-menu />
        </div>
    </div>
</header>
