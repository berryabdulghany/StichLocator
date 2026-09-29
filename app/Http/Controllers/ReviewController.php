<?php

namespace App\Http\Controllers;

use App\Enums\ReviewTag;
use App\Models\Location;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Ulasan dari sisi pengguna. Pengelolaan ulasan oleh admin ada di Admin\ReviewController.
 */
class ReviewController extends Controller
{
    /**
     * Daftar ulasan untuk satu penjahit (publik, JSON).
     */
    public function getReviews($locationId)
    {
        $location = Location::published()->findOrFail($locationId);

        $reviews = $location->reviews()
            ->with('user')
            ->latest()
            ->get()
            ->map(fn (Review $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'review' => $review->review,
                'tags' => $review->tags ?? [],
                'user_name' => $review->user?->name ?? 'Anonymous',
                'created_at' => $review->created_at->format('Y-m-d H:i:s'),
            ]);

        return response()->json($reviews);
    }

    /**
     * Pengguna yang login menulis ulasan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Hanya penjahit yang terbit (dan tidak di tempat sampah) yang bisa diulas
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_published', true)->whereNull('deleted_at')],
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:500',
            ...self::tagRules(),
        ]);

        $review = Review::create([
            'location_id' => $validated['location_id'],
            'user_id' => Auth::id(),
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'tags' => array_values(array_unique($validated['tags'] ?? [])),
        ]);

        Location::find($validated['location_id'])?->refreshRatingStats();

        return response()->json([
            'success' => true,
            'message' => __('Review added.'),
            'data' => $review,
        ]);
    }

    /**
     * Pengguna menghapus ulasannya sendiri dari halaman profil.
     */
    public function destroyOwn(Review $review)
    {
        // Hanya pemilik ulasan yang boleh menghapus
        abort_unless($review->user_id === Auth::id(), 403);

        $location = $review->location;
        $review->forceDelete(); // pilihan pengguna sendiri: langsung permanen, tidak masuk sampah admin
        $location?->refreshRatingStats();

        return redirect()->to(route('user.profile') . '#ulasan')->with('status', __('Review deleted.'));
    }

    /**
     * Aturan validasi tag cepat ulasan (App\Enums\ReviewTag). Dipakai juga oleh admin.
     */
    public static function tagRules(): array
    {
        return [
            'tags' => 'nullable|array|max:' . count(ReviewTag::cases()),
            'tags.*' => ['string', Rule::enum(ReviewTag::class)],
        ];
    }
}
