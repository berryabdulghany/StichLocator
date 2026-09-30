@extends('layouts.admin', ['title' => __('Edit review')])

@section('actions')
    <a href="{{ route('admin.reviews.index') }}" class="btn-ghost hidden py-1.5 sm:inline-flex">
        <i class="ti ti-arrow-left" aria-hidden="true"></i>{{ __('Back to list') }}
    </a>
@endsection

@section('content')
    <div class="max-w-2xl">
        <div class="card mb-6 flex items-center gap-3 p-4 text-sm">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-navy-50 font-bold text-navy-700">
                {{ mb_strtoupper(mb_substr($review->user->name ?? '?', 0, 1)) }}
            </span>
            <div>
                <p><span class="font-semibold text-stone-900">{{ $review->user->name ?? __('Anonymous') }}</span>
                    <span class="text-stone-400">→</span> {{ $review->location->name ?? __('Deleted tailor') }}</p>
                <p class="text-xs text-stone-500">{{ $review->created_at->translatedFormat('j F Y, H:i') }}</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert-error mb-6" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('admin.reviews.update', $review) }}" method="POST" class="card space-y-5 p-6">
            @csrf
            @method('PUT')

            <div>
                <label for="rating" class="label">{{ __('Rating') }}</label>
                <select name="rating" id="rating" class="input w-auto">
                    @for ($star = 5; $star >= 1; $star--)
                        <option value="{{ $star }}" @selected(old('rating', $review->rating) == $star)>{{ str_repeat('★', $star) }} ({{ $star }})</option>
                    @endfor
                </select>
            </div>

            <div>
                <label for="review" class="label">{{ __('Review') }}</label>
                <textarea name="review" id="review" rows="4" maxlength="500" class="input" required>{{ old('review', $review->review) }}</textarea>
            </div>

            <fieldset>
                <legend class="label">{{ __('Tags') }}</legend>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($reviewTags as $tag)
                        <label class="relative cursor-pointer">
                            <input type="checkbox" name="tags[]" value="{{ $tag->value }}" class="peer sr-only"
                                   @checked(in_array($tag->value, old('tags', $review->tags ?? []), true))>
                            <span class="chip peer-checked:border-navy-700 peer-checked:bg-navy-50 peer-checked:text-navy-700 peer-focus-visible:ring-2 peer-focus-visible:ring-navy-600/60">{{ $tag->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <label for="reply" class="label">{{ __('Reply from the tailor') }}</label>
                <textarea name="reply" id="reply" rows="3" maxlength="500" class="input">{{ old('reply', $review->reply) }}</textarea>
                <p class="mt-1 text-xs text-stone-500">{{ __('Written by the partner tailor. Empty it to remove an inappropriate reply.') }}</p>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary">{{ __('Save changes') }}</button>
                <a href="{{ route('admin.reviews.index') }}" class="btn-ghost">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
