@extends('layouts.admin', ['title' => __('Dashboard')])

@section('actions')
    <a href="{{ route('admin.tailors.create') }}" class="btn-primary hidden py-1.5 sm:inline-flex">
        <i class="ti ti-plus" aria-hidden="true"></i>{{ __('Add tailor') }}
    </a>
@endsection

@section('content')
    {{-- ============================== KARTU STATISTIK ============================== --}}
    @php
        $cards = [
            ['icon' => 'needle-thread', 'label' => __('Tailors'), 'value' => $stats['tailors'], 'note' => __(':count open now', ['count' => $stats['open_now']]), 'route' => 'admin.tailors.index'],
            ['icon' => 'message-circle', 'label' => __('Reviews'), 'value' => $stats['reviews'], 'note' => __('+:count in the last 30 days', ['count' => $stats['reviews_recent']]), 'route' => 'admin.reviews.index'],
            ['icon' => 'star', 'label' => __('Average rating'), 'value' => number_format($stats['rating'], 1), 'note' => __('from all reviews'), 'route' => 'admin.reviews.index'],
            ['icon' => 'users', 'label' => __('Users'), 'value' => $stats['users'], 'note' => __('+:count in the last 30 days', ['count' => $stats['users_recent']]), 'route' => 'admin.users.index'],
        ];
    @endphp

    @if ($todo['reports'] || $todo['drafts'])
        <div class="mb-6 flex flex-wrap gap-3">
            @if ($todo['reports'])
                <a href="{{ route('admin.reviews.index', ['reported' => 1]) }}" class="flex items-center gap-2 rounded-lg border border-terra-200 bg-terra-50 px-4 py-2.5 text-sm font-medium text-terra-800 hover:bg-terra-100">
                    <i class="ti ti-flag" aria-hidden="true"></i>{{ trans_choice(':count reported review needs checking|:count reported reviews need checking', $todo['reports']) }}
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
            @if ($todo['drafts'])
                <a href="{{ route('admin.tailors.index', ['status' => 'draft']) }}" class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-900 hover:bg-amber-100">
                    <i class="ti ti-eye-off" aria-hidden="true"></i>{{ trans_choice(':count tailor is still a draft|:count tailors are still drafts', $todo['drafts']) }}
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <a href="{{ route($card['route']) }}" class="card group flex items-start gap-4 p-5 transition hover:border-navy-300 hover:shadow-md">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-navy-50 text-xl text-navy-700 transition group-hover:bg-navy-700 group-hover:text-white">
                    <i class="ti ti-{{ $card['icon'] }}" aria-hidden="true"></i>
                </span>
                <div>
                    <p class="text-sm text-stone-500">{{ $card['label'] }}</p>
                    <p class="text-2xl font-bold text-stone-900">{{ $card['value'] }}</p>
                    <p class="mt-0.5 text-xs text-stone-500">{{ $card['note'] }}</p>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- ============================== GRAFIK ULASAN HARIAN ============================== --}}
        <section class="card p-5 xl:col-span-2" aria-labelledby="chart-title">
            <div class="flex items-baseline justify-between gap-2">
                <h2 id="chart-title" class="font-semibold text-stone-900">{{ __('Reviews per day, last 30 days') }}</h2>
                <p class="text-sm text-stone-500">{{ __(':count total', ['count' => $timeline->sum('value')]) }}</p>
            </div>
            <x-daily-bars class="mt-5" :days="$timeline" :caption="__('Reviews per day, last 30 days')" unit=":count review|:count reviews" />
        </section>

        {{-- ============================== DISTRIBUSI RATING ============================== --}}
        @php($ratingTotal = max(1, $distribution->sum()))
        <section class="card p-5" aria-labelledby="rating-title">
            <h2 id="rating-title" class="font-semibold text-stone-900">{{ __('Rating distribution') }}</h2>
            <dl class="mt-5 space-y-3">
                @foreach ($distribution->sortKeysDesc() as $star => $count)
                    <div class="flex items-center gap-3 text-sm">
                        <dt class="w-8 shrink-0 text-stone-600">{{ $star }} <span class="rating-star">★</span></dt>
                        <dd class="flex flex-1 items-center gap-3">
                            <span class="h-2.5 flex-1 rounded-full bg-stone-100">
                                <span class="block h-2.5 rounded-full bg-terra-500" style="width: {{ round($count / $ratingTotal * 100) }}%"></span>
                            </span>
                            <span class="w-16 shrink-0 text-right text-stone-600">{{ $count }} <span class="text-stone-400">({{ round($count / $ratingTotal * 100) }}%)</span></span>
                        </dd>
                    </div>
                @endforeach
            </dl>

            <h2 class="mt-8 font-semibold text-stone-900">{{ __('Tailors per category') }}</h2>
            @php($categoryMax = max(1, $categories->max('value')))
            <dl class="mt-4 space-y-3">
                @foreach ($categories as $category)
                    <div class="flex items-center gap-3 text-sm">
                        <dt class="w-20 shrink-0 truncate text-stone-600">{{ $category['label'] }}</dt>
                        <dd class="flex flex-1 items-center gap-3">
                            <span class="h-2.5 flex-1 rounded-full bg-stone-100">
                                <span class="block h-2.5 rounded-full bg-navy-600" style="width: {{ round($category['value'] / $categoryMax * 100) }}%"></span>
                            </span>
                            <span class="w-6 shrink-0 text-right text-stone-600">{{ $category['value'] }}</span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- ============================== ULASAN TERBARU ============================== --}}
        <section class="card xl:col-span-2" aria-labelledby="recent-title">
            <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4">
                <h2 id="recent-title" class="font-semibold text-stone-900">{{ __('Latest reviews') }}</h2>
                <a href="{{ route('admin.reviews.index') }}" class="text-sm font-semibold text-navy-700 hover:underline">{{ __('See all') }}</a>
            </div>
            <ul class="divide-y divide-stone-100">
                @forelse ($recentReviews as $review)
                    <li class="flex gap-3 px-5 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-50 text-sm font-bold text-navy-700">
                            {{ mb_strtoupper(mb_substr($review->user->name ?? '?', 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm">
                                <span class="font-semibold text-stone-900">{{ $review->user->name ?? __('Anonymous') }}</span>
                                <span class="text-stone-400">→</span>
                                <span class="text-stone-700">{{ $review->location->name ?? __('Deleted tailor') }}</span>
                            </p>
                            <p class="truncate text-sm text-stone-500">{{ $review->review }}</p>
                        </div>
                        <div class="shrink-0 text-right text-xs">
                            <p class="rating-star">{{ str_repeat('★', $review->rating) }}</p>
                            <p class="text-stone-400">{{ $review->created_at->diffForHumans() }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-stone-500">{{ __('No reviews yet.') }}</li>
                @endforelse
            </ul>
        </section>

        {{-- ============================== PERLU PERHATIAN ============================== --}}
        <section class="card" aria-labelledby="attention-title">
            <div class="border-b border-stone-100 px-5 py-4">
                <h2 id="attention-title" class="font-semibold text-stone-900">{{ __('Needs attention') }}</h2>
                <p class="text-xs text-stone-500">{{ __('Tailors with the lowest rating') }}</p>
            </div>
            <ul class="divide-y divide-stone-100">
                @forelse ($needsAttention as $tailor)
                    <li>
                        <a href="{{ route('admin.tailors.edit', $tailor) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-stone-50">
                            <img src="{{ $tailor->cover_url }}" alt="" class="h-10 w-10 rounded-lg bg-navy-50 object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-stone-900">{{ $tailor->name }}</p>
                                <p class="text-xs text-stone-500">{{ trans_choice(':count review|:count reviews', $tailor->review_count, ['count' => $tailor->review_count]) }}</p>
                            </div>
                            <span class="text-sm font-semibold text-stone-700"><span class="rating-star">★</span> {{ number_format($tailor->rating, 1) }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-stone-500">{{ __('No reviews yet.') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
