<?php

namespace App\Http\Controllers\Mitra;

use App\Enums\ServiceCategory;
use App\Http\Requests\Admin\TailorRequest;
use App\Support\Activity;
use App\Support\TailorEditor;

/**
 * Mitra mengubah profil usahanya sendiri. Perubahan langsung tayang dan tercatat di log aktivitas;
 * status terbit/draf tetap diatur admin.
 */
class ProfileController extends MitraController
{
    public function edit()
    {
        $location = $this->location()->load(['services', 'hours', 'photos']);

        return view('mitra.profile', [
            'tailor' => $location,
            'week' => $location->weeklyHours(),
            'categories' => ServiceCategory::cases(),
        ]);
    }

    public function update(TailorRequest $request)
    {
        $location = TailorEditor::save($this->location(), $request, canPublish: false);

        Activity::log('updated', $location, 'Updated tailor :name', ['name' => $location->name]);

        return redirect()->route('mitra.profile.edit')->with('status', __('Changes saved. They are now live on the map.'));
    }
}
