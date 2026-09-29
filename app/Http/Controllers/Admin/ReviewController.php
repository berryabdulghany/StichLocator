<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewTag;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ReviewController as PublicReviewController;
use App\Models\Location;
use App\Models\Review;
use Illuminate\Http\Request;

/**
 * Moderasi ulasan: cari, saring, ubah, dan hapus.
 * Admin tidak membuat ulasan sendiri supaya semua ulasan berasal dari pelanggan.
 */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'rating' => $request->integer('rating') ?: null,
            'tailor' => $request->integer('tailor') ?: null,
        ];

        $reviews = Review::query()
            ->with(['user', 'location'])
            ->when($filters['q'], fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('review', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))))
            ->when($filters['rating'], fn ($query, $rating) => $query->where('rating', $rating))
            ->when($filters['tailor'], fn ($query, $tailor) => $query->where('location_id', $tailor))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $tailors = Location::orderBy('name')->get(['id', 'name']);

        return view('admin.reviews.index', compact('reviews', 'filters', 'tailors'));
    }

    public function edit(Review $review)
    {
        $review->load(['user', 'location']);

        return view('admin.reviews.edit', [
            'review' => $review,
            'reviewTags' => ReviewTag::cases(),
        ]);
    }

    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'max:500'],
            ...PublicReviewController::tagRules(),
        ]);

        $review->update([
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'tags' => array_values(array_unique($validated['tags'] ?? [])),
        ]);
        $review->location?->refreshRatingStats();

        return redirect()->route('admin.reviews.index')->with('status', __('Review updated.'));
    }

    public function destroy(Review $review)
    {
        $location = $review->location;
        $review->delete();
        $location?->refreshRatingStats();

        return back()->with('status', __('Review deleted.'));
    }
}
