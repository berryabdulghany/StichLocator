@extends('layouts.admin', ['title' => __('Reviews')])

@section('content')
    <form method="GET" class="mb-5 grid gap-2 sm:grid-cols-[minmax(0,1fr)_12rem_10rem_auto]" role="search">
        <div class="relative">
            <i class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $filters['q'] }}" class="input pl-9" placeholder="{{ __('Search review text or user name...') }}" aria-label="{{ __('Search reviews') }}">
        </div>
        <select name="tailor" class="input" aria-label="{{ __('Filter by tailor') }}">
            <option value="">{{ __('All tailors') }}</option>
            @foreach ($tailors as $tailor)
                <option value="{{ $tailor->id }}" @selected($filters['tailor'] === $tailor->id)>{{ $tailor->name }}</option>
            @endforeach
        </select>
        <select name="rating" class="input" aria-label="{{ __('Filter by rating') }}">
            <option value="">{{ __('All ratings') }}</option>
            @for ($star = 5; $star >= 1; $star--)
                <option value="{{ $star }}" @selected($filters['rating'] === $star)>{{ str_repeat('★', $star) }}</option>
            @endfor
        </select>
        <div class="flex gap-2">
            <button type="submit" class="btn-outline flex-1">{{ __('Filter') }}</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.reviews.index') }}" class="btn-ghost" title="{{ __('Reset filters') }}" aria-label="{{ __('Reset filters') }}"><i class="ti ti-x" aria-hidden="true"></i></a>
            @endif
        </div>
    </form>

    <div class="card divide-y divide-stone-100">
        @forelse ($reviews as $review)
            <article class="flex flex-col gap-3 p-5 sm:flex-row">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                        <span class="rating-star">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                        <span class="font-semibold text-stone-900">{{ $review->user->name ?? __('Anonymous') }}</span>
                        <span class="text-stone-400">→</span>
                        @if ($review->location)
                            <a href="{{ route('admin.tailors.edit', $review->location) }}" class="text-navy-700 hover:underline">{{ $review->location->name }}</a>
                        @else
                            <span class="text-stone-400">{{ __('Deleted tailor') }}</span>
                        @endif
                        <span class="text-xs text-stone-400">· {{ $review->created_at->translatedFormat('j M Y, H:i') }}</span>
                    </div>
                    <p class="mt-1.5 text-sm text-stone-700">{{ $review->review }}</p>
                    @if ($review->tags)
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($review->tags as $tag)
                                @if ($label = \App\Enums\ReviewTag::tryFrom($tag)?->label())
                                    <span class="badge-tag">{{ $label }}</span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="flex shrink-0 gap-1 sm:items-start">
                    <a href="{{ route('admin.reviews.edit', $review) }}" class="btn-ghost px-2 py-1.5" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                        <i class="ti ti-pencil" aria-hidden="true"></i>
                    </a>
                    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" data-confirm="{{ __('Delete this review?') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-ghost px-2 py-1.5 text-red-600 hover:bg-red-50" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                            <i class="ti ti-trash" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <p class="px-5 py-12 text-center text-stone-500">{{ __('No reviews match the filter.') }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $reviews->links() }}</div>
@endsection
