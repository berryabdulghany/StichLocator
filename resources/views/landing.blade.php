@extends('layouts.site', ['title' => __('Find a tailor near you')])

@push('head')
    <meta name="description" content="{{ __('Find tailors in Bandung: compare prices, read reviews, and chat directly on WhatsApp.') }}">
    <meta property="og:title" content="{{ config('app.name') }} · {{ __('Find a tailor near you') }}">
    <meta property="og:description" content="{{ __('Find tailors in Bandung: compare prices, read reviews, and chat directly on WhatsApp.') }}">
    @if ($heroTailor?->cover_url)
        <meta property="og:image" content="{{ $heroTailor->cover_url }}">
    @endif
@endpush

@section('content')
<main>
    {{-- ============================== HERO ============================== --}}
    <section class="relative overflow-hidden bg-white">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-12 lg:grid-cols-[1.1fr_0.9fr] lg:px-6 lg:py-20">
            <div>
                <span class="badge bg-navy-50 px-3 py-1 text-navy-700">
                    <i class="ti ti-map-pin" aria-hidden="true"></i>{{ __('Tailors in Bandung') }}
                </span>

                <h1 class="mt-4 text-4xl font-bold leading-[1.2] tracking-tight text-stone-900 sm:text-5xl sm:leading-[1.2]">
                    {{ __('Find the right') }}
                    {{-- Garis jahitan di bawah kata kunci (text-decoration, jadi tidak menabrak baris berikutnya) --}}
                    <span class="whitespace-nowrap text-navy-700 underline decoration-terra-500 decoration-dashed decoration-2 underline-offset-[6px] sm:decoration-[3px]">{{ __('tailor') }}</span>
                    {{ __('near you') }}
                </h1>

                <p class="mt-5 max-w-xl text-lg leading-relaxed text-stone-600">
                    {{ __('Compare prices, check opening hours and reviews, then chat with the tailor directly on WhatsApp. Kebaya, alterations, uniforms, all the way to suits.') }}
                </p>

                {{-- Pencarian: membuka halaman peta dengan filter terisi --}}
                <form action="{{ route('dashboard') }}" method="GET" role="search"
                      class="mt-8 flex flex-col gap-2 rounded-2xl border border-stone-200 bg-white p-2 shadow-lg shadow-navy-900/5 sm:flex-row sm:items-center sm:rounded-full sm:pl-5">
                    <label class="min-w-0 flex-1">
                        <span class="block text-[11px] font-semibold text-stone-800">{{ __('Service') }}</span>
                        <input type="search" name="q" autocomplete="off"
                               class="w-full border-0 bg-transparent p-0 text-sm text-stone-700 placeholder-stone-400 focus:outline-none focus:ring-0"
                               placeholder="{{ __('Kebaya, alterations, tailor name...') }}">
                    </label>
                    <label class="min-w-0 border-stone-200 sm:border-l sm:pl-4">
                        <span class="block text-[11px] font-semibold text-stone-800">{{ __('Area') }}</span>
                        <select name="wilayah" class="w-full cursor-pointer border-0 bg-transparent p-0 pr-6 text-sm text-stone-700 focus:outline-none focus:ring-0">
                            <option value="">{{ __('All areas') }}</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area }}">{{ $area }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="btn-accent rounded-full px-5 py-3">
                        <i class="ti ti-search" aria-hidden="true"></i>{{ __('Find tailors') }}
                    </button>
                </form>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <a href="{{ route('dashboard', ['dekat' => 1]) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-navy-700 hover:underline">
                        <i class="ti ti-current-location" aria-hidden="true"></i>{{ __('Find near me') }}
                    </a>
                    <span class="text-stone-300" aria-hidden="true">·</span>
                    @foreach ($categories->take(3) as $category)
                        <a href="{{ route('dashboard', ['kategori' => $category['value']]) }}" class="chip">
                            <i class="ti ti-{{ $category['icon'] }}" aria-hidden="true"></i>{{ $category['label'] }}
                        </a>
                    @endforeach
                </div>

                {{-- Statistik --}}
                <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-dashed border-stitch pt-6">
                    <div>
                        <dt class="text-xs text-stone-500">{{ __('Tailors') }}</dt>
                        <dd class="text-2xl font-bold text-stone-900">{{ $stats['tailors'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-stone-500">{{ __('Reviews') }}</dt>
                        <dd class="text-2xl font-bold text-stone-900">{{ $stats['reviews'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-stone-500">{{ __('Average rating') }}</dt>
                        <dd class="text-2xl font-bold text-stone-900"><span class="rating-star">★</span> {{ number_format($stats['rating'], 1) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Visual hero: foto penjahit + kartu-kartu UI yang melayang --}}
            @if ($heroTailor)
                <div class="relative mx-auto w-full max-w-md" aria-hidden="true">
                    <div class="aspect-[4/5] overflow-hidden rounded-3xl bg-navy-50 shadow-2xl shadow-navy-900/20">
                        <img src="{{ $heroTailor->cover_url }}" alt="" class="h-full w-full object-cover">
                    </div>

                    {{-- Kartu status --}}
                    <div class="floating absolute -left-3 top-8 w-60 p-3 sm:-left-6">
                        <div class="flex items-center gap-2">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-navy-700 text-white">
                                <i class="ti ti-needle-thread" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-stone-900">{{ $heroTailor->name }}</p>
                                <p class="text-xs text-stone-500"><span class="rating-star">★ {{ number_format($heroTailor->rating, 1) }}</span> · {{ $heroTailor->area() }}</p>
                            </div>
                        </div>
                        <span class="mt-2 {{ $heroTailor->isOpenNow() ? 'badge-open' : 'badge-closed' }}">{{ $heroTailor->statusText() }}</span>
                    </div>

                    {{-- Kartu "nota jahit" --}}
                    <div class="card-stitch absolute -right-3 bottom-10 w-64 p-4 shadow-lg sm:-right-6">
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold text-stone-800">
                            <i class="ti ti-receipt text-navy-700" aria-hidden="true"></i>{{ __('Services and prices') }}
                        </p>
                        @foreach ($heroTailor->services->take(3) as $service)
                            <div class="flex items-baseline py-1 text-xs">
                                <span class="truncate text-stone-700">{{ $service->name }}</span>
                                <span class="nota-leader"></span>
                                <span class="whitespace-nowrap font-semibold text-stone-900">{{ \App\Support\Format::rupiahShort($service->price_from) }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pin khas StichLocator --}}
                    <div class="absolute right-10 top-6">
                        <span class="sl-pin" style="transform: none"><i class="ti ti-needle-thread"></i><span>{{ number_format($heroTailor->rating, 1) }}</span></span>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- ============================== KATEGORI ============================== --}}
    <section id="kategori" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-16 lg:px-6" aria-labelledby="kategori-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="kategori-title" class="text-2xl font-bold text-stone-900 sm:text-3xl">{{ __('What do you need sewn?') }}</h2>
                <p class="mt-2 text-stone-600">{{ __('Choose a category to see the tailors that offer it on the map.') }}</p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($categories as $category)
                <a href="{{ route('dashboard', ['kategori' => $category['value']]) }}"
                   class="card group p-5 transition hover:-translate-y-1 hover:border-navy-300 hover:shadow-lg">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-navy-50 text-2xl text-navy-700 transition group-hover:bg-navy-700 group-hover:text-white">
                        <i class="ti ti-{{ $category['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <p class="mt-4 font-semibold text-stone-900">{{ $category['label'] }}</p>
                    <p class="mt-1 text-xs text-stone-500">
                        {{ trans_choice(':count tailor|:count tailors', $category['count'], ['count' => $category['count']]) }}
                        @if ($category['price_from'])
                            · {{ __('From') }} <span class="font-semibold text-stone-700">{{ $category['price_from'] }}</span>
                        @endif
                    </p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============================== CARA KERJA ============================== --}}
    <section id="cara-kerja" class="scroll-mt-20 bg-white py-16" aria-labelledby="cara-kerja-title">
        <div class="mx-auto max-w-6xl px-4 lg:px-6">
            <h2 id="cara-kerja-title" class="text-center text-2xl font-bold text-stone-900 sm:text-3xl">{{ __('How it works') }}</h2>
            <p class="mx-auto mt-2 max-w-xl text-center text-stone-600">{{ __('From searching to your first chat with a tailor, in three steps.') }}</p>

            @php
                $steps = [
                    ['icon' => 'search', 'title' => __('Search'), 'text' => __('Pick a service and area, or search around your current location.')],
                    ['icon' => 'receipt', 'title' => __('Compare'), 'text' => __('See the price list, opening hours, photos, and customer reviews.')],
                    ['icon' => 'brand-whatsapp', 'title' => __('Contact'), 'text' => __('Chat with the tailor on WhatsApp or ask for a home visit to take measurements.')],
                ];
            @endphp

            <ol class="relative mt-12 grid gap-10 md:grid-cols-3">
                {{-- Garis jahitan yang menghubungkan langkah (desktop) --}}
                <span class="absolute left-[16%] right-[16%] top-7 hidden border-t-2 border-dashed border-stitch md:block" aria-hidden="true"></span>
                @foreach ($steps as $i => $step)
                    <li class="relative text-center">
                        <span class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-navy-700 text-2xl text-white ring-8 ring-white">
                            <i class="ti ti-{{ $step['icon'] }}" aria-hidden="true"></i>
                        </span>
                        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-terra-600">{{ __('Step :number', ['number' => $i + 1]) }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-stone-900">{{ $step['title'] }}</h3>
                        <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-stone-600">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ============================== FITUR ============================== --}}
    <section class="mx-auto max-w-6xl px-4 py-16 lg:px-6" aria-labelledby="fitur-title">
        <h2 id="fitur-title" class="text-2xl font-bold text-stone-900 sm:text-3xl">{{ __('Made for finding tailors') }}</h2>
        <p class="mt-2 max-w-2xl text-stone-600">{{ __('Not just a map. Everything you need to choose a tailor, in one place.') }}</p>

        @php
            $features = [
                ['icon' => 'ruler-measure', 'title' => __('Measuring-tape radius'), 'text' => __('See tailors within 1, 2, 5, or 10 km of where you are.')],
                ['icon' => 'receipt', 'title' => __('Transparent price list'), 'text' => __('Starting prices and turnaround time for every service, before you visit.')],
                ['icon' => 'route', 'title' => __('Route and navigation'), 'text' => __('Preview the route on the map, then continue in Google Maps.')],
                ['icon' => 'home-move', 'title' => __('Home visit'), 'text' => __('Find tailors who come to your home to take measurements.')],
            ];
        @endphp

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($features as $feature)
                <div class="card-stitch p-5">
                    <i class="ti ti-{{ $feature['icon'] }} text-3xl text-terra-500" aria-hidden="true"></i>
                    <h3 class="mt-3 font-semibold text-stone-900">{{ $feature['title'] }}</h3>
                    <p class="mt-1 text-sm leading-relaxed text-stone-600">{{ $feature['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================== PENJAHIT TERATAS ============================== --}}
    <section id="penjahit-teratas" class="scroll-mt-20 bg-white py-16" aria-labelledby="teratas-title">
        <div class="mx-auto max-w-6xl px-4 lg:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="teratas-title" class="text-2xl font-bold text-stone-900 sm:text-3xl">{{ __('Top-rated tailors') }}</h2>
                    <p class="mt-2 text-stone-600">{{ __('Chosen by customers through their ratings and reviews.') }}</p>
                </div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 font-semibold text-navy-700 hover:underline">
                    {{ __('See all on the map') }} <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($topTailors as $tailor)
                    <a href="{{ $tailor['detail_url'] }}" class="group overflow-hidden rounded-2xl border border-stone-200 bg-white transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="relative aspect-[4/3] overflow-hidden bg-navy-50">
                            <img src="{{ $tailor['cover_url'] }}" alt="{{ $tailor['name'] }}" loading="lazy"
                                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute left-3 top-3 {{ $tailor['is_open'] ? 'badge-open' : 'badge-closed' }} shadow-sm">
                                {{ $tailor['is_open'] ? __('Open') : __('Closed') }}
                            </span>
                        </div>
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-semibold text-stone-900">{{ $tailor['name'] }}</h3>
                                <span class="rating-star shrink-0 text-sm font-semibold">★ {{ number_format($tailor['rating'], 1) }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-stone-500">{{ $tailor['area'] }} · {{ trans_choice(':count review|:count reviews', $tailor['reviews_count'], ['count' => $tailor['reviews_count']]) }}</p>
                            @if ($tailor['price_from_text'])
                                <p class="mt-3 text-sm text-stone-600">{{ __('From') }} <span class="font-semibold text-stone-900">{{ $tailor['price_from_text'] }}</span></p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== CTA ============================== --}}
    <section class="px-4 py-16 lg:px-6">
        <div class="relative mx-auto max-w-6xl overflow-hidden rounded-3xl bg-navy-700 px-6 py-12 text-center sm:px-12">
            {{-- Motif jahitan di tepi dalam --}}
            <div class="pointer-events-none absolute inset-3 rounded-2xl border-2 border-dashed border-white/20" aria-hidden="true"></div>
            <h2 class="relative text-2xl font-bold text-white sm:text-3xl">{{ __('Ready to find your favourite tailor?') }}</h2>
            <p class="relative mx-auto mt-3 max-w-xl text-navy-100">{{ __('Open the map, pick a category, and chat with a tailor in minutes.') }}</p>
            <div class="relative mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('dashboard') }}" class="btn-accent px-6 py-3">
                    <i class="ti ti-map-2" aria-hidden="true"></i>{{ __('Open the map') }}
                </a>
                @guest
                    <a href="{{ route('register') }}" class="btn px-6 py-3 text-white ring-1 ring-white/40 hover:bg-white/10">{{ __('Sign up for free') }}</a>
                @endguest
            </div>
        </div>
    </section>
</main>

@include('partials.footer')
@endsection
