<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Kelola akun admin dari dalam panel (pengganti registrasi admin publik).
 */
class AdminAccountController extends Controller
{
    public function index()
    {
        return view('admin.admins.index', ['admins' => Admin::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validateWithBag('createAdmin', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $admin = Admin::create($validated); // password di-hash oleh cast 'hashed'
        Activity::log('created', $admin, 'Added admin :name (:email)', ['name' => $admin->name, 'email' => $admin->email]);

        return back()->with('status', __('Admin :name added.', ['name' => $validated['name']]));
    }

    public function destroy(Admin $admin)
    {
        // Jangan sampai admin menghapus akunnya sendiri (dan terkunci dari panel)
        if ($admin->is(Auth::guard('admin')->user())) {
            return back()->withErrors(['admin' => __('You cannot delete your own account.')]);
        }

        $admin->delete();
        Activity::log('deleted', $admin, 'Removed admin :name (:email)', ['name' => $admin->name, 'email' => $admin->email]);

        return back()->with('status', __('Admin :name deleted.', ['name' => $admin->name]));
    }
}
