@extends('layouts.admin', ['title' => __('Trash')])

@section('content')
    <div class="mb-6 flex items-start gap-2 rounded-lg border border-navy-100 bg-navy-50 px-4 py-3 text-sm text-navy-800" role="note">
        <i class="ti ti-info-circle mt-0.5 text-lg" aria-hidden="true"></i>
        <p>{{ __('Deleted items stay here for :days days so they can be restored, then they are permanently deleted automatically.', ['days' => $days]) }}</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        {{-- Penjahit --}}
        <section class="card divide-y divide-stone-100 self-start" aria-labelledby="trash-tailors">
            <h2 id="trash-tailors" class="flex items-center gap-2 px-5 py-4 font-semibold text-stone-900">
                <i class="ti ti-needle-thread text-navy-700" aria-hidden="true"></i>{{ __('Tailors') }}
                <span class="text-sm font-normal text-stone-400">{{ $tailors->count() }}</span>
            </h2>
            @forelse ($tailors as $tailor)
                @php($left = max(0, $days - (int) $tailor->deleted_at->diffInDays(now())))
                <div class="flex items-center gap-3 px-5 py-3">
                    <img src="{{ $tailor->cover_url }}" alt="" class="h-11 w-11 shrink-0 rounded-lg bg-navy-50 object-cover opacity-70">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-stone-900">{{ $tailor->name }}</p>
                        <p class="text-xs text-stone-500">
                            {{ __('Deleted :time', ['time' => $tailor->deleted_at->diffForHumans()]) }}
                            · {{ trans_choice(':count review|:count reviews', $tailor->reviews_count) }}
                            · <span class="text-terra-700">{{ trans_choice('deleted in :count day|deleted in :count days', $left) }}</span>
                        </p>
                    </div>
                    <form action="{{ route('admin.trash.tailors.restore', $tailor) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-outline px-2.5 py-1.5 text-sm" title="{{ __('Restore') }}">
                            <i class="ti ti-restore" aria-hidden="true"></i><span class="hidden sm:inline">{{ __('Restore') }}</span>
                        </button>
                    </form>
                    <form action="{{ route('admin.trash.tailors.destroy', $tailor) }}" method="POST"
                          data-confirm="{{ __('Permanently delete :name? Photos and reviews are deleted too. This cannot be undone.', ['name' => $tailor->name]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-ghost px-2 py-1.5 text-red-600 hover:bg-red-50" title="{{ __('Delete permanently') }}" aria-label="{{ __('Delete permanently') }}">
                            <i class="ti ti-trash-x" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-stone-500">{{ __('No deleted tailors.') }}</p>
            @endforelse
        </section>

        {{-- Ulasan --}}
        <section class="card divide-y divide-stone-100 self-start" aria-labelledby="trash-reviews">
            <h2 id="trash-reviews" class="flex items-center gap-2 px-5 py-4 font-semibold text-stone-900">
                <i class="ti ti-message-circle text-navy-700" aria-hidden="true"></i>{{ __('Reviews') }}
                <span class="text-sm font-normal text-stone-400">{{ $reviews->count() }}</span>
            </h2>
            @forelse ($reviews as $review)
                @php($left = max(0, $days - (int) $review->deleted_at->diffInDays(now())))
                <div class="flex items-start gap-3 px-5 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm">
                            <span class="rating-star">{{ str_repeat('★', $review->rating) }}</span>
                            <span class="font-semibold text-stone-900">{{ $review->user->name ?? __('Anonymous') }}</span>
                            <span class="text-stone-400">→</span>
                            <span class="text-stone-700">{{ $review->location->name ?? __('Deleted tailor') }}</span>
                        </p>
                        <p class="mt-1 line-clamp-2 text-sm text-stone-600">{{ $review->review }}</p>
                        <p class="mt-1 text-xs text-stone-500">
                            {{ __('Deleted :time', ['time' => $review->deleted_at->diffForHumans()]) }}
                            · <span class="text-terra-700">{{ trans_choice('deleted in :count day|deleted in :count days', $left) }}</span>
                        </p>
                    </div>
                    @if ($review->location && ! $review->location->trashed())
                        <form action="{{ route('admin.trash.reviews.restore', $review) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-outline px-2.5 py-1.5 text-sm" title="{{ __('Restore') }}">
                                <i class="ti ti-restore" aria-hidden="true"></i><span class="hidden sm:inline">{{ __('Restore') }}</span>
                            </button>
                        </form>
                    @else
                        <span class="badge bg-stone-100 text-stone-500" title="{{ __('Restore the tailor first') }}">{{ __('Tailor in trash') }}</span>
                    @endif
                    <form action="{{ route('admin.trash.reviews.destroy', $review) }}" method="POST"
                          data-confirm="{{ __('Permanently delete this review? This cannot be undone.') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-ghost px-2 py-1.5 text-red-600 hover:bg-red-50" title="{{ __('Delete permanently') }}" aria-label="{{ __('Delete permanently') }}">
                            <i class="ti ti-trash-x" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-stone-500">{{ __('No deleted reviews.') }}</p>
            @endforelse
        </section>
    </div>
@endsection
