@extends('layouts.mitra', ['title' => __('Dashboard')])

@php
    $metrics = [
        'views' => ['label' => __('Page views'), 'icon' => 'eye', 'unit' => ':count view|:count views', 'bar' => 'bg-navy-600 group-hover:bg-navy-800'],
        'whatsapp' => ['label' => __('WhatsApp clicks'), 'icon' => 'brand-whatsapp', 'unit' => ':count click|:count clicks', 'bar' => 'bg-terra-500 group-hover:bg-terra-700'],
        'route' => ['label' => __('Route clicks'), 'icon' => 'route', 'unit' => ':count click|:count clicks', 'bar' => 'bg-navy-400 group-hover:bg-navy-600'],
    ];
    $closed = $location->isTemporarilyClosed();
    $done = collect($checklist)->where('done', true)->count();
@endphp

@section('actions')
    <a href="{{ route('mitra.profile.edit') }}" class="btn-primary py-1.5">
        <i class="ti ti-pencil" aria-hidden="true"></i><span class="hidden sm:inline">{{ __('Edit profile') }}</span>
    </a>
@endsection

@section('content')
    <p class="mb-6 text-stone-600">{{ __('Hi, :name!', ['name' => auth('tailor')->user()->name]) }} {{ __('Here is how your page is doing.') }}</p>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- ============================== STATUS USAHA ============================== --}}
        <section class="card flex flex-col gap-4 p-5 sm:flex-row xl:col-span-2" aria-labelledby="status-title">
            <img src="{{ $location->cover_url }}" alt="" class="h-28 w-full shrink-0 rounded-xl bg-navy-50 object-cover sm:w-40">
            <div class="min-w-0 flex-1">
                <h2 id="status-title" class="text-lg font-bold text-stone-900">{{ $location->name }}</h2>
                <p class="truncate text-sm text-stone-500">{{ $location->address }}</p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @unless ($location->is_published)
                        <span class="badge bg-amber-50 text-amber-800"><i class="ti ti-eye-off" aria-hidden="true"></i>{{ __('Draft: not visible yet') }}</span>
                    @endunless
                    <span class="{{ $location->isOpenNow() ? 'badge-open' : 'badge-closed' }}">{{ $location->statusText() }}</span>
                    @if ($location->rating !== null)
                        <span class="badge-tag">★ {{ number_format($location->rating, 1) }} · {{ trans_choice(':count review|:count reviews', $location->review_count, ['count' => $location->review_count]) }}</span>
                    @endif
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('penjahit.show', $location->slug) }}" target="_blank" rel="noopener" class="btn-outline px-3 py-1.5 text-sm">
                        <i class="ti ti-external-link" aria-hidden="true"></i>{{ __('View my page') }}
                    </a>
                    <a href="{{ route('mitra.reviews.index', ['filter' => 'unreplied']) }}" class="btn-ghost px-3 py-1.5 text-sm">
                        <i class="ti ti-message-circle" aria-hidden="true"></i>{{ trans_choice(':count review awaiting reply|:count reviews awaiting reply', $unrepliedCount) }}
                    </a>
                </div>
            </div>
        </section>

        {{-- ============================== LIBUR SEMENTARA ============================== --}}
        <section @class(['card p-5', 'border-amber-300 bg-amber-50/40' => $closed]) aria-labelledby="closure-title">
            <h2 id="closure-title" class="flex items-center gap-2 font-semibold text-stone-900">
                <i class="ti ti-calendar-off text-navy-700" aria-hidden="true"></i>{{ __('Temporary closure') }}
            </h2>

            @if ($closed)
                <p class="mt-2 text-sm text-stone-700">
                    {{ __('Customers see that you are closed until :date.', ['date' => $location->closed_until->translatedFormat('l, j F Y')]) }}
                </p>
                @if ($location->closure_note)
                    <p class="mt-1 text-sm italic text-stone-500">"{{ $location->closure_note }}"</p>
                @endif
                <form action="{{ route('mitra.closure.destroy') }}" method="POST" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-primary w-full">
                        <i class="ti ti-door-enter" aria-hidden="true"></i>{{ __('Open again now') }}
                    </button>
                </form>
            @else
                <p class="mt-1 text-sm text-stone-500">{{ __('Going on holiday? Mark your shop as closed without changing the weekly hours.') }}</p>
                @if ($errors->closure->any())
                    <div class="alert-error mt-3" role="alert">{{ $errors->closure->first() }}</div>
                @endif
                <form action="{{ route('mitra.closure.update') }}" method="POST" class="mt-3 space-y-2">
                    @csrf
                    <label for="dash-closed-until" class="label">{{ __('Closed until (inclusive)') }}</label>
                    <input type="date" name="closed_until" id="dash-closed-until" value="{{ old('closed_until') }}" required
                           min="{{ now(\App\Models\Location::TIMEZONE)->toDateString() }}" class="input">
                    <label for="dash-closure-note" class="sr-only">{{ __('Note for customers (optional)') }}</label>
                    <input type="text" name="closure_note" id="dash-closure-note" value="{{ old('closure_note') }}" maxlength="120" class="input"
                           placeholder="{{ __('Note for customers (optional)') }}">
                    <button type="submit" class="btn-outline w-full">{{ __('Mark as closed') }}</button>
                </form>
            @endif
        </section>
    </div>

    {{-- ============================== STATISTIK 30 HARI ============================== --}}
    <section class="card mt-6 p-5" aria-labelledby="stats-title" data-tabs>
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="stats-title" class="font-semibold text-stone-900">{{ __('Last 30 days') }}</h2>
            <p class="text-xs text-stone-500">{{ __('Each visitor is counted once per day. Your own visits are not counted.') }}</p>
        </div>

        {{-- Kartu angka sekaligus tab untuk memilih grafik --}}
        <div class="mt-4 grid gap-3 sm:grid-cols-3" role="tablist" aria-label="{{ __('Choose a chart') }}">
            @foreach ($metrics as $key => $metric)
                <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        data-tab="{{ $key }}"
                        class="flex items-center gap-3 rounded-xl border border-stone-200 p-4 text-left transition hover:border-navy-300 aria-selected:border-navy-600 aria-selected:bg-navy-50/60 aria-selected:ring-1 aria-selected:ring-navy-600">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-lg text-navy-700 shadow-sm">
                        <i class="ti ti-{{ $metric['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <span>
                        <span class="block text-sm text-stone-500">{{ $metric['label'] }}</span>
                        <span class="block text-2xl font-bold text-stone-900">{{ number_format($totals[$key], 0, ',', '.') }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        @foreach ($metrics as $key => $metric)
            <div role="tabpanel" id="panel-{{ $key }}" aria-labelledby="tab-{{ $key }}" class="mt-6" @unless ($loop->first) hidden @endunless>
                <p class="mb-3 text-sm font-medium text-stone-700">{{ __(':metric per day', ['metric' => $metric['label']]) }}</p>
                <x-daily-bars :days="$timeline->map(fn ($day) => ['label' => $day['label'], 'value' => $day[$key]])"
                              :caption="__(':metric per day', ['metric' => $metric['label']])" :unit="$metric['unit']" :bar="$metric['bar']" />
            </div>
        @endforeach

        @if ($totals['views'] === 0)
            <p class="mt-4 text-sm text-stone-500">{{ __('No visits recorded yet. Share your page link on WhatsApp or Instagram to get your first customers.') }}</p>
        @endif
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- ============================== ULASAN MENUNGGU BALASAN ============================== --}}
        <section class="card xl:col-span-2" aria-labelledby="unreplied-title">
            <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4">
                <h2 id="unreplied-title" class="font-semibold text-stone-900">{{ __('Reviews awaiting your reply') }}</h2>
                <a href="{{ route('mitra.reviews.index') }}" class="text-sm font-semibold text-navy-700 hover:underline">{{ __('See all') }}</a>
            </div>
            <ul class="divide-y divide-stone-100">
                @forelse ($unreplied as $review)
                    <li class="flex gap-3 px-5 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-50 text-sm font-bold text-navy-700">
                            {{ mb_strtoupper(mb_substr($review->user->name ?? '?', 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm">
                                <span class="font-semibold text-stone-900">{{ $review->user->name ?? __('Anonymous') }}</span>
                                <span class="rating-star ml-1 text-xs">{{ str_repeat('★', $review->rating) }}</span>
                            </p>
                            <p class="truncate text-sm text-stone-500">{{ $review->review }}</p>
                        </div>
                        <a href="{{ route('mitra.reviews.index', ['filter' => 'unreplied']) }}#review-{{ $review->id }}" class="btn-outline shrink-0 self-center px-3 py-1.5 text-sm">
                            {{ __('Reply') }}
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-stone-500">{{ __('All reviews have been replied to. Nice work!') }}</li>
                @endforelse
            </ul>
        </section>

        {{-- ============================== KELENGKAPAN PROFIL ============================== --}}
        <section class="card p-5" aria-labelledby="checklist-title">
            <div class="flex items-baseline justify-between">
                <h2 id="checklist-title" class="font-semibold text-stone-900">{{ __('Profile checklist') }}</h2>
                <span class="text-sm text-stone-500">{{ $done }}/{{ count($checklist) }}</span>
            </div>
            <div class="mt-3 h-2 rounded-full bg-stone-100" aria-hidden="true">
                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ round($done / count($checklist) * 100) }}%"></div>
            </div>
            <ul class="mt-4 space-y-3">
                @foreach ($checklist as $item)
                    <li class="flex gap-2.5 text-sm">
                        <i @class([
                            'ti mt-0.5 text-lg',
                            'ti-circle-check-filled text-emerald-600' => $item['done'],
                            'ti-circle-dashed text-stone-300' => ! $item['done'],
                        ]) aria-hidden="true"></i>
                        <span>
                            <span @class(['block font-medium', 'text-stone-400 line-through' => $item['done'], 'text-stone-800' => ! $item['done']])>
                                {{ $item['label'] }}<span class="sr-only">: {{ $item['done'] ? __('done') : __('not done yet') }}</span>
                            </span>
                            @unless ($item['done'])
                                <span class="block text-xs text-stone-500">{{ $item['hint'] }}</span>
                            @endunless
                        </span>
                    </li>
                @endforeach
            </ul>
            @if ($done < count($checklist))
                <a href="{{ route('mitra.profile.edit') }}" class="btn-outline mt-4 w-full">{{ __('Complete profile') }}</a>
            @endif
        </section>
    </div>
@endsection
