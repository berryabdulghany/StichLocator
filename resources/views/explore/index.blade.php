@extends('layouts.site', ['fullHeight' => true, 'title' => __('Find a tailor near you')])

@section('header-search')
    {{-- Pencarian bersegmen: layanan/nama | wilayah | tombol cari --}}
    <form id="search-form" class="flex items-center rounded-full border border-stone-300 bg-white py-1 pl-4 pr-1 shadow-sm focus-within:border-navy-600" role="search">
        <label class="min-w-0 flex-1">
            <span class="block text-[11px] font-semibold text-stone-800">{{ __('Service') }}</span>
            <input id="search-query" type="search" autocomplete="off"
                   class="w-full border-0 bg-transparent p-0 text-sm text-stone-700 placeholder-stone-400 focus:outline-none focus:ring-0"
                   placeholder="{{ __('Kebaya, alterations, tailor name...') }}">
        </label>
        <label class="ml-3 hidden min-w-0 border-l border-stone-200 pl-3 sm:block">
            <span class="block text-[11px] font-semibold text-stone-800">{{ __('Area') }}</span>
            <select id="search-area" class="w-full cursor-pointer border-0 bg-transparent p-0 pr-6 text-sm text-stone-700 focus:outline-none focus:ring-0">
                <option value="">{{ __('All areas') }}</option>
                @foreach ($areas as $area)
                    <option value="{{ $area }}">{{ $area }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="ml-2 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-terra-500 text-white hover:bg-terra-600" aria-label="{{ __('Search') }}">
            <i class="ti ti-search" aria-hidden="true"></i>
        </button>
    </form>
@endsection

@section('content')
    <div class="relative flex min-h-0 flex-1">
        {{-- Daftar penjahit: kolom kiri di desktop, bottom sheet di HP --}}
        <section id="results-panel" aria-label="{{ __('Tailor list') }}"
                 class="results-sheet absolute inset-x-0 bottom-0 z-[1050] flex h-[85%] flex-col rounded-t-2xl border-t border-stone-200 bg-white shadow-[0_-8px_24px_rgba(0,0,0,0.08)]
                        lg:static lg:z-auto lg:h-auto lg:w-[400px] lg:shrink-0 lg:transform-none lg:rounded-none lg:border-r lg:border-t-0 lg:shadow-none">
            {{-- Pegangan bottom sheet (HP) --}}
            <button type="button" id="sheet-handle" class="flex w-full touch-none flex-col items-center pb-1 pt-2 lg:hidden" aria-label="{{ __('Show list') }}">
                <span class="h-1.5 w-10 rounded-full bg-stone-300"></span>
            </button>

            <div class="flex items-center justify-between gap-2 border-b border-dashed border-stitch px-4 pb-3 pt-1 lg:pt-3">
                <p id="results-count" class="text-sm font-semibold text-stone-800" aria-live="polite"></p>
                <select id="sort-select" class="input w-auto py-1.5 pr-8 text-xs" aria-label="{{ __('Sort by') }}">
                    <option value="distance" hidden disabled>{{ __('Nearest') }}</option>
                    <option value="recommended">{{ __('Recommended') }}</option>
                    <option value="rating">{{ __('Highest rated') }}</option>
                    <option value="price">{{ __('Lowest price') }}</option>
                    <option value="reviews">{{ __('Most reviewed') }}</option>
                </select>
            </div>

            {{-- Radius "pita ukur", muncul setelah lokasi pengguna ditemukan --}}
            <div id="radius-bar" class="hidden flex-wrap items-center gap-x-1.5 gap-y-2 border-b border-dashed border-stitch px-4 py-2 text-xs">
                <i class="ti ti-ruler-measure text-base text-navy-700" aria-hidden="true"></i>
                <span class="mr-1 font-medium text-stone-600">{{ __('Radius') }}</span>
                @foreach ([1, 2, 5, 10] as $km)
                    <button type="button" class="chip px-2.5 py-1" data-radius="{{ $km * 1000 }}">{{ $km }} km</button>
                @endforeach
                <button type="button" class="chip px-2.5 py-1" data-radius="">{{ __('All') }}</button>
                <button type="button" id="pick-location" class="ml-auto inline-flex items-center gap-1 whitespace-nowrap font-semibold text-navy-700 hover:underline"
                        title="{{ __('Tap the map at your location.') }}">
                    <i class="ti ti-map-pin" aria-hidden="true"></i>{{ __('Correct location') }}
                </button>
            </div>

            <ul id="tailor-list" class="flex-1 space-y-1 overflow-y-auto p-2"></ul>

            <div id="empty-state" class="hidden flex-1 flex-col items-center justify-center p-8 text-center">
                <i class="ti ti-needle-thread mb-2 text-4xl text-stone-300" aria-hidden="true"></i>
                <p class="font-semibold text-stone-700">{{ __('No tailors match your search') }}</p>
                <p class="mt-1 text-sm text-stone-500">{{ __('Try another category or area.') }}</p>
                <button type="button" id="reset-filters" class="btn-outline mt-4">{{ __('Reset filters') }}</button>
            </div>
        </section>

        {{-- Peta --}}
        <div class="relative min-w-0 flex-1">
            <div id="map" class="absolute inset-0 z-0" aria-label="{{ __('Tailor map') }}"></div>

            {{-- Chip kategori & filter cepat yang melayang di atas peta (ala Google Maps) --}}
            <nav class="pointer-events-none absolute inset-x-0 top-0 z-[1000] px-3 pt-3" aria-label="{{ __('Categories') }}">
                <div class="pointer-events-auto flex w-fit max-w-full gap-2 overflow-x-auto pb-2 scrollbar-hide">
                    <button type="button" class="map-chip is-active" data-category="" aria-pressed="true">
                        <i class="ti ti-hanger" aria-hidden="true"></i>{{ __('All') }}
                    </button>
                    @foreach ($categories as $category)
                        <button type="button" class="map-chip" data-category="{{ $category['value'] }}" aria-pressed="false">
                            <i class="ti ti-{{ $category['icon'] }}" aria-hidden="true"></i>{{ $category['label'] }}
                        </button>
                    @endforeach

                    <span class="mx-0.5 my-1.5 w-px shrink-0 bg-stone-300" aria-hidden="true"></span>

                    <button type="button" class="map-chip" data-filter="open" aria-pressed="false">
                        <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>{{ __('Open now') }}
                    </button>
                    <button type="button" class="map-chip" data-filter="homeVisit" aria-pressed="false">
                        <i class="ti ti-home-move" aria-hidden="true"></i>{{ __('Home visit') }}
                    </button>
                    <button type="button" class="map-chip" data-filter="saved" aria-pressed="false">
                        <i class="ti ti-bookmark" aria-hidden="true"></i>{{ __('Saved') }}
                        <span id="saved-count" class="rounded-full bg-stone-100 px-1.5 text-xs text-stone-600"></span>
                    </button>
                </div>
            </nav>

            <label id="bounds-control" class="floating absolute bottom-3 left-3 z-[1000] hidden cursor-pointer items-center gap-2 px-3 py-2 text-xs font-medium text-stone-700 lg:flex">
                <input id="bounds-toggle" type="checkbox" class="h-4 w-4 rounded border-stone-300 text-navy-700 focus:ring-navy-600">
                {{ __('Search as I move the map') }}
            </label>

            {{-- Pesan singkat (misalnya izin lokasi ditolak) --}}
            <div id="toast" role="status" aria-live="polite"
                 class="pointer-events-none absolute left-1/2 top-16 z-[1200] hidden max-w-[90%] -translate-x-1/2 rounded-lg bg-stone-900/90 px-4 py-2 text-center text-sm text-white shadow-lg"></div>

            {{-- Kartu pratinjau rute --}}
            <div id="route-card" class="card-stitch absolute inset-x-3 bottom-[8.5rem] z-[1070] hidden p-3 shadow-lg lg:bottom-3 lg:left-3 lg:right-auto lg:w-[360px]"></div>

            {{-- Kartu preview saat marker diketuk (HP) --}}
            <div id="map-preview" class="card-stitch absolute inset-x-3 bottom-[8.5rem] z-[1060] hidden p-2 shadow-lg lg:hidden"></div>

            {{-- Drawer detail penjahit --}}
            <aside id="detail-drawer" aria-label="{{ __('Tailor details') }}" aria-hidden="true"
                   class="detail-drawer fixed inset-0 z-[1300] flex flex-col bg-white
                          lg:absolute lg:inset-y-3 lg:left-auto lg:right-3 lg:w-[420px] lg:rounded-2xl lg:border lg:border-stone-200 lg:shadow-xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-4 py-2">
                    <button type="button" id="drawer-close" class="btn-ghost -ml-2 px-2" aria-label="{{ __('Close') }}">
                        <i class="ti ti-arrow-left text-lg lg:hidden" aria-hidden="true"></i>
                        <i class="ti ti-x hidden text-lg lg:inline" aria-hidden="true"></i>
                    </button>
                    <a id="drawer-permalink" href="#" class="text-xs font-semibold text-navy-700 hover:underline">
                        {{ __('Open full page') }} <i class="ti ti-external-link" aria-hidden="true"></i>
                    </a>
                </div>
                <div id="drawer-body" class="flex-1 overflow-y-auto"></div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.EXPLORER = {
            tailors: @json($tailors),
            categories: @json($categories),
            homeUrl: @json(route('dashboard')),
            routeUrl: @json(route('route.preview')),
            routingEnabled: @json(filled(config('services.openrouteservice.key'))),
        };
    </script>
    @vite('resources/js/explorer.js')
@endpush
