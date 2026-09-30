@extends('layouts.site', ['title' => $location->name])

@push('head')
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($location->description ?? $location->address, 155) }}">
    <meta property="og:title" content="{{ $location->name }} · {{ config('app.name') }}">
    <meta property="og:description" content="{{ $location->statusText() }} · {{ $location->address }}">
    @if ($location->cover_url)
        <meta property="og:image" content="{{ $location->cover_url }}">
    @endif
@endpush

@section('content')
    <main class="mx-auto grid max-w-6xl gap-6 px-4 py-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:px-6">
        <div>
            <a href="{{ route('dashboard') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-navy-700 hover:underline">
                <i class="ti ti-arrow-left" aria-hidden="true"></i>{{ __('Back to map') }}
            </a>
            <div class="card overflow-hidden">
                @include('penjahit.partials.detail')
            </div>
        </div>

        <aside class="lg:sticky lg:top-6 lg:self-start">
            <div class="card overflow-hidden">
                <div id="mini-map" class="h-64" data-lat="{{ $location->lat }}" data-lng="{{ $location->lng }}" data-name="{{ $location->name }}"
                     data-open="{{ $location->isOpenNow() ? '1' : '0' }}" aria-label="{{ __('Tailor map') }}"></div>
                <div class="p-4 text-sm text-stone-600">
                    <p class="flex items-start gap-1.5"><i class="ti ti-map-pin mt-0.5 text-navy-700" aria-hidden="true"></i>{{ $location->address }}</p>
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ $location->lat }},{{ $location->lng }}" target="_blank" rel="noopener"
                       data-track="route" data-track-url="{{ route('penjahit.track', $location->id) }}" class="btn-outline mt-3 w-full"><i class="ti ti-route" aria-hidden="true"></i>{{ __('Get directions') }}</a>
                </div>
            </div>
        </aside>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/penjahit.js')
@endpush
