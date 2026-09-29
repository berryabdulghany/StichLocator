<?php

namespace App\Http\Controllers;

use App\Enums\ReviewTag;
use App\Models\Review;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews for admin panel
     */
    public function index()
    {
        if (request()->expectsJson()) {
            $reviews = Review::with(['user', 'location'])
                ->latest()
                ->get()
                ->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'nama_penjahit' => $review->location ? $review->location->name : 'Lokasi Dihapus',
                        'rating' => $review->rating,
                        'review' => $review->review,
                        'created_at' => $review->created_at->format('d M Y H:i')
                    ];
                });

            return response()->json($reviews);
        }

        $locations = Location::all();
        return view('admin.rating_review', compact('locations'));
    }

    /**
     * Get reviews for a specific location (publik)
     */
    public function getReviews($locationId)
    {
        $reviews = Review::where('location_id', $locationId)
            ->with('user')
            ->latest()
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'review' => $review->review,
                    'tags' => $review->tags ?? [],
                    'user_name' => $review->user ? $review->user->name : 'Anonymous',
                    'created_at' => $review->created_at->format('Y-m-d H:i:s')
                ];
            });

        return response()->json($reviews);
    }

    /**
     * Store a new review.
     * Dipakai oleh pengguna (guard web) dan admin (guard admin, tanpa user_id).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:500',
            ...$this->tagRules(),
        ]);

        $review = Review::create([
            'location_id' => $validated['location_id'],
            'user_id' => Auth::guard('web')->id(),
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'tags' => array_values(array_unique($validated['tags'] ?? [])),
        ]);

        $this->updateLocationAverageRating($validated['location_id']);

        return response()->json([
            'success' => true,
            'message' => __('Review added.'),
            'data' => $review
        ]);
    }

    /**
     * Show a specific review
     */
    public function show($id)
    {
        $review = Review::with(['location', 'user'])->find($id);

        if (! $review) {
            return response()->json([
                'success' => false,
                'message' => __('Review not found.')
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $review->id,
                'nama_penjahit' => $review->location ? $review->location->name : 'Lokasi Dihapus',
                'rating' => $review->rating,
                'review' => $review->review,
                'user_name' => $review->user ? $review->user->name : 'Anonymous',
                'created_at' => $review->created_at->format('d M Y H:i')
            ]
        ]);
    }

    /**
     * Update a review
     */
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:500',
            ...$this->tagRules(),
        ]);

        if ($request->has('tags')) {
            $validated['tags'] = array_values(array_unique($validated['tags'] ?? []));
        }

        $review->update($validated);

        $this->updateLocationAverageRating($review->location_id);

        return response()->json([
            'success' => true,
            'message' => __('Review updated.'),
            'data' => $review
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $locationId = $review->location_id;

        $review->delete();

        $this->updateLocationAverageRating($locationId);

        return response()->json([
            'success' => true,
            'message' => __('Review deleted.')
        ]);
    }

    /**
     * Pengguna menghapus ulasannya sendiri dari halaman profil.
     */
    public function destroyOwn(Review $review)
    {
        // Hanya pemilik ulasan yang boleh menghapus
        abort_unless($review->user_id === Auth::id(), 403);

        $locationId = $review->location_id;
        $review->delete();
        $this->updateLocationAverageRating($locationId);

        return redirect()->to(route('user.profile') . '#ulasan')->with('status', __('Review deleted.'));
    }

    /**
     * Aturan validasi tag cepat ulasan (App\Enums\ReviewTag)
     */
    private function tagRules(): array
    {
        return [
            'tags' => 'nullable|array|max:' . count(ReviewTag::cases()),
            'tags.*' => ['string', Rule::enum(ReviewTag::class)],
        ];
    }

    /**
     * Update location's average rating & jumlah ulasan
     */
    private function updateLocationAverageRating($locationId)
    {
        $location = Location::find($locationId);

        if ($location) {
            $averageRating = Review::where('location_id', $locationId)->avg('rating') ?? 0;
            $location->update([
                'rating' => round($averageRating, 1),
                'reviews' => Review::where('location_id', $locationId)->count(),
            ]);
        }
    }
}
