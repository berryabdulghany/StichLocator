<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Review;
use App\Support\Activity;
use Illuminate\Support\Str;

/**
 * Tempat sampah: penjahit & ulasan yang dihapus bisa dipulihkan selama 30 hari,
 * setelah itu dihapus permanen otomatis (php artisan model:prune, dijadwalkan harian).
 */
class TrashController extends Controller
{
    public function index()
    {
        $tailors = Location::onlyTrashed()->withCount('reviews')->latest('deleted_at')->get();

        $reviews = Review::onlyTrashed()
            ->with(['user', 'location' => fn ($query) => $query->withTrashed()])
            ->latest('deleted_at')
            ->get();

        return view('admin.trash', [
            'tailors' => $tailors,
            'reviews' => $reviews,
            'days' => Location::TRASH_DAYS,
        ]);
    }

    public function restoreTailor(Location $tailor)
    {
        $tailor->restore();
        Activity::log('restored', $tailor, 'Restored tailor :name from trash', ['name' => $tailor->name]);

        return back()->with('status', __('Tailor :name restored.', ['name' => $tailor->name]));
    }

    public function forceDeleteTailor(Location $tailor)
    {
        $name = $tailor->name;
        $tailor->forceDelete(); // file sampul & galeri ikut dihapus (lihat Location::booted)
        Activity::log('force_deleted', null, 'Permanently deleted tailor :name', ['name' => $name]);

        return back()->with('status', __('Tailor :name permanently deleted.', ['name' => $name]));
    }

    public function restoreReview(Review $review)
    {
        $review->restore();
        $review->location?->refreshRatingStats();
        Activity::log('restored', $review, 'Restored a review by :user from trash', ['user' => $review->user?->name ?? __('Anonymous')]);

        return back()->with('status', __('Review restored.'));
    }

    public function forceDeleteReview(Review $review)
    {
        $excerpt = Str::limit($review->review, 60);
        $review->forceDelete();
        Activity::log('force_deleted', null, 'Permanently deleted a review: ":excerpt"', ['excerpt' => $excerpt]);

        return back()->with('status', __('Review permanently deleted.'));
    }
}
