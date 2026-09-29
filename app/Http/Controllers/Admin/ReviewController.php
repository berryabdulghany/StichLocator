<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewTag;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ReviewController as PublicReviewController;
use App\Models\Location;
use App\Models\Review;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Moderasi ulasan: cari, saring (termasuk yang dilaporkan), ubah, tindak lanjuti laporan, dan hapus.
 * Admin tidak membuat ulasan sendiri supaya semua ulasan berasal dari pelanggan.
 */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $reviews = $this->filteredQuery($filters)
            ->with(['user', 'location', 'reports' => fn ($query) => $query->open()->with('user')])
            ->withCount(['reports as open_reports_count' => fn ($query) => $query->open()])
            ->when($filters['reported'], fn ($query) => $query->orderByDesc('open_reports_count'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $tailors = Location::orderBy('name')->get(['id', 'name']);
        $reportedCount = Review::whereHas('reports', fn ($query) => $query->open())->count();

        return view('admin.reviews.index', compact('reviews', 'filters', 'tailors', 'reportedCount'));
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

        Activity::log('updated', $review, 'Edited a review by :user on :tailor', $this->logProperties($review));

        return redirect()->route('admin.reviews.index')->with('status', __('Review updated.'));
    }

    /**
     * Pindahkan ke tempat sampah. Laporan terbuka atas ulasan ini ikut dianggap selesai.
     */
    public function destroy(Review $review)
    {
        $location = $review->location;
        $review->reports()->open()->update(['resolved_at' => now(), 'resolved_by' => auth('admin')->id()]);
        $review->delete();
        $location?->refreshRatingStats();

        Activity::log('deleted', $review, 'Moved a review by :user on :tailor to trash', $this->logProperties($review));

        return back()->with('status', __('Review moved to trash.'));
    }

    /**
     * Laporan dinilai tidak berdasar: ulasan tetap tampil, laporan ditandai selesai.
     */
    public function dismissReports(Review $review)
    {
        $count = $review->reports()->open()->update(['resolved_at' => now(), 'resolved_by' => auth('admin')->id()]);

        Activity::log('dismissed', $review, 'Dismissed :count reports on a review by :user', [
            ...$this->logProperties($review),
            'count' => $count,
        ]);

        return back()->with('status', __('Reports dismissed. The review stays visible.'));
    }

    /**
     * Ekspor ulasan (mengikuti filter yang sedang aktif) ke CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $reviews = $this->filteredQuery($this->filters($request))
            ->with(['user', 'location'])
            ->withCount(['reports as open_reports_count' => fn ($query) => $query->open()])
            ->latest()
            ->get();

        Activity::log('exported', null, 'Exported :count reviews to CSV', ['count' => $reviews->count()]);

        return CsvExport::download('ulasan-' . now()->format('Y-m-d') . '.csv', [
            'ID', 'Tanggal', 'Penjahit', 'Pengguna', 'Rating', 'Ulasan', 'Tag', 'Laporan terbuka',
        ], $reviews->map(fn (Review $review) => [
            $review->id,
            $review->created_at->format('Y-m-d H:i'),
            $review->location?->name,
            $review->user?->name,
            $review->rating,
            $review->review,
            implode(', ', $review->tags ?? []),
            $review->open_reports_count,
        ]));
    }

    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q')),
            'rating' => $request->integer('rating') ?: null,
            'tailor' => $request->integer('tailor') ?: null,
            'reported' => $request->boolean('reported'),
        ];
    }

    private function filteredQuery(array $filters)
    {
        return Review::query()
            ->when($filters['q'], fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('review', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))))
            ->when($filters['rating'], fn ($query, $rating) => $query->where('rating', $rating))
            ->when($filters['tailor'], fn ($query, $tailor) => $query->where('location_id', $tailor))
            ->when($filters['reported'], fn ($query) => $query->whereHas('reports', fn ($q) => $q->open()));
    }

    private function logProperties(Review $review): array
    {
        return [
            'user' => $review->user?->name ?? __('Anonymous'),
            'tailor' => $review->location?->name ?? '-',
            'excerpt' => Str::limit($review->review, 60),
        ];
    }
}
