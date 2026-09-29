<?php

namespace App\Http\Controllers;

use App\Enums\ReportReason;
use App\Models\Review;
use App\Models\ReviewReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengguna melaporkan ulasan yang tidak pantas. Admin menindaklanjuti di panel admin.
 */
class ReviewReportController extends Controller
{
    public function store(Request $request, Review $review)
    {
        $user = $request->user();

        // Tidak masuk akal melaporkan ulasan sendiri (bisa dihapus dari profil)
        abort_if($review->user_id === $user->id, 403);

        $validated = $request->validate([
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        // Satu pengguna satu laporan per ulasan; laporan ulang memperbarui alasan & membuka kembali
        ReviewReport::updateOrCreate(
            ['review_id' => $review->id, 'user_id' => $user->id],
            [...$validated, 'resolved_at' => null, 'resolved_by' => null],
        );

        $message = __('Thanks, your report has been sent to the admin.');

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }
}
