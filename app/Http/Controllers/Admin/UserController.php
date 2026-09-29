<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->withCount('reviews')
            ->when($search, fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    /**
     * Hapus pengguna beserta ulasannya; rating penjahit terkait dihitung ulang.
     */
    public function destroy(User $user)
    {
        $locations = $user->reviews()->with('location')->get()->pluck('location')->filter()->unique('id');

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $reviewCount = $user->reviews()->withTrashed()->count();
        $user->delete(); // ulasan ikut terhapus permanen (cascade di database)
        $locations->each->refreshRatingStats();

        Activity::log('deleted', $user, 'Deleted user :name (:email) and :count reviews', [
            'name' => $user->name,
            'email' => $user->email,
            'count' => $reviewCount,
        ]);

        return back()->with('status', __('User :name deleted.', ['name' => $user->name]));
    }
}
