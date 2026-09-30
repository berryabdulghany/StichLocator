@extends('layouts.mitra', ['title' => __('Reviews')])

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex w-fit gap-1 rounded-lg bg-stone-100 p-1 text-sm" aria-label="{{ __('Filter reviews') }}">
            @foreach ([null => __('All reviews'), 'unreplied' => __('Awaiting reply')] as $value => $label)
                <a href="{{ route('mitra.reviews.index', array_filter(['filter' => $value])) }}"
                   @if ($filter === ($value ?: null)) aria-current="page" @endif
                   @class([
                       'flex items-center gap-1.5 rounded-md px-3 py-1.5 font-medium transition',
                       'bg-white text-stone-900 shadow-sm' => $filter === ($value ?: null),
                       'text-stone-500 hover:text-stone-800' => $filter !== ($value ?: null),
                   ])>
                    {{ $label }}
                    @if ($value && $unrepliedCount)
                        <span class="rounded-full bg-terra-500 px-1.5 text-xs font-bold text-white">{{ $unrepliedCount }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
        @if ($location->rating !== null)
            <p class="text-sm text-stone-600">
                <span class="rating-star">★</span> <b class="font-semibold text-stone-900">{{ number_format($location->rating, 1) }}</b>
                · {{ trans_choice(':count review|:count reviews', $location->review_count, ['count' => $location->review_count]) }}
            </p>
        @endif
    </div>

    <p class="mb-5 text-sm text-stone-500">
        {{ __('Replies appear publicly under the review. Thank happy customers and respond calmly to complaints; future customers read both.') }}
    </p>

    <div class="space-y-4">
        @forelse ($reviews as $review)
            @php($bag = $errors->getBag("reply{$review->id}"))
            <article id="review-{{ $review->id }}" class="card scroll-mt-24 p-5">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                    <span class="rating-star">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                    <span class="font-semibold text-stone-900">{{ $review->user->name ?? __('Anonymous') }}</span>
                    <span class="text-xs text-stone-400">· {{ $review->created_at->translatedFormat('j M Y') }}</span>
                    @unless ($review->reply)
                        <span class="badge ml-auto bg-terra-50 text-terra-800">{{ __('Awaiting reply') }}</span>
                    @endunless
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

                @if ($review->reply)
                    <div class="mt-4 rounded-lg border-l-2 border-navy-600 bg-navy-50/60 p-3">
                        <p class="text-xs font-semibold text-navy-800">
                            {{ __('Your reply') }} <span class="font-normal text-stone-500">· {{ $review->replied_at?->diffForHumans() }}</span>
                        </p>
                        <p class="mt-1 whitespace-pre-line text-sm text-stone-700">{{ $review->reply }}</p>
                    </div>
                @endif

                <details class="mt-3" @if ($bag->any() || ! $review->reply) open @endif>
                    @if ($review->reply)
                        <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-semibold text-navy-700 hover:underline [&::-webkit-details-marker]:hidden">
                            <i class="ti ti-pencil" aria-hidden="true"></i>{{ __('Edit reply') }}
                        </summary>
                    @else
                        <summary class="sr-only">{{ __('Write a reply') }}</summary>
                    @endif
                    <form action="{{ route('mitra.reviews.reply', $review) }}" method="POST" class="mt-2 space-y-2">
                        @csrf
                        @method('PUT')
                        <label for="reply-{{ $review->id }}" class="sr-only">{{ __('Write a reply') }}</label>
                        <textarea name="reply" id="reply-{{ $review->id }}" rows="3" maxlength="500" required class="input"
                                  placeholder="{{ __('Thank the customer or respond to their feedback...') }}">{{ $bag->any() ? old('reply') : $review->reply }}</textarea>
                        @if ($bag->any())
                            <p class="text-sm text-red-600" role="alert">{{ $bag->first() }}</p>
                        @endif
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn-primary px-3 py-1.5 text-sm">
                                <i class="ti ti-send" aria-hidden="true"></i>{{ $review->reply ? __('Update reply') : __('Publish reply') }}
                            </button>
                            @if ($review->reply)
                                <button type="submit" form="delete-reply-{{ $review->id }}" class="btn-ghost px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">
                                    {{ __('Remove reply') }}
                                </button>
                            @endif
                        </div>
                    </form>
                </details>
                @if ($review->reply)
                    <form id="delete-reply-{{ $review->id }}" action="{{ route('mitra.reviews.reply.destroy', $review) }}" method="POST"
                          data-confirm="{{ __('Remove your reply to this review?') }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </article>
        @empty
            <div class="card px-5 py-12 text-center text-stone-500">
                {{ $filter ? __('All reviews have been replied to. Nice work!') : __('No reviews yet.') }}
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $reviews->links() }}</div>
@endsection
