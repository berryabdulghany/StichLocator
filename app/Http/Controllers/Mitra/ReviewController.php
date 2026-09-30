<?php

namespace App\Http\Controllers\Mitra;

use App\Models\Review;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Ulasan pelanggan untuk penjahit milik mitra, beserta balasannya.
 * Mitra tidak bisa mengubah atau menghapus ulasan (itu wewenang admin lewat laporan).
 */
class ReviewController extends MitraController
{
    public function index(Request $request)
    {
        $filter = $request->query('filter') === 'unreplied' ? 'unreplied' : null;
        $location = $this->location();

        $reviews = $location->reviews()
            ->with('user')
            ->when($filter, fn ($query) => $query->whereNull('reply'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('mitra.reviews', [
            'reviews' => $reviews,
            'filter' => $filter,
            'unrepliedCount' => $location->reviews()->whereNull('reply')->count(),
            'location' => $location,
        ]);
    }

    public function reply(Request $request, Review $review)
    {
        $this->authorizeReview($review);

        $validated = $request->validateWithBag("reply{$review->id}", [
            'reply' => ['required', 'string', 'max:500'],
        ], [], ['reply' => __('reply')]);

        $isNew = $review->reply === null;
        $review->update(['reply' => $validated['reply'], 'replied_at' => now()]);

        Activity::log('replied', $review, $isNew ? 'Replied to a review by :user' : 'Edited the reply to a review by :user', [
            'user' => $review->user?->name ?? __('Anonymous'),
            'excerpt' => Str::limit($review->review, 60),
        ]);

        return redirect()->to(url()->previous() . "#review-{$review->id}")->with('status', __('Reply published.'));
    }

    public function destroyReply(Review $review)
    {
        $this->authorizeReview($review);

        $review->update(['reply' => null, 'replied_at' => null]);

        Activity::log('updated', $review, 'Removed the reply to a review by :user', [
            'user' => $review->user?->name ?? __('Anonymous'),
        ]);

        return back()->with('status', __('Reply removed.'));
    }

    /** Ulasan penjahit lain dianggap tidak ada */
    private function authorizeReview(Review $review): void
    {
        abort_unless($review->location_id === $this->account()->location_id, 404);
    }
}
