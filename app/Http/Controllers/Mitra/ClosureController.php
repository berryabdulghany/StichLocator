<?php

namespace App\Http\Controllers\Mitra;

use App\Support\Activity;
use Illuminate\Http\Request;

/**
 * Libur sementara dari dasbor mitra (misalnya mudik atau renovasi) tanpa mengubah jadwal mingguan.
 */
class ClosureController extends MitraController
{
    public function update(Request $request)
    {
        $validated = $request->validateWithBag('closure', [
            'closed_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:' . now()->addYear()->toDateString()],
            'closure_note' => ['nullable', 'string', 'max:120'],
        ], [], [
            'closed_until' => __('closed until'),
            'closure_note' => __('closure note'),
        ]);

        $location = $this->location();
        $location->update($validated);

        Activity::log('closed', $location, 'Marked :name temporarily closed until :date', [
            'name' => $location->name,
            'date' => $location->closed_until->toDateString(),
        ]);

        return back()->with('status', __('Temporary closure saved.'));
    }

    public function destroy()
    {
        $location = $this->location();
        $location->update(['closed_until' => null, 'closure_note' => null]);

        Activity::log('reopened', $location, 'Reopened :name', ['name' => $location->name]);

        return back()->with('status', __('Welcome back! Your regular opening hours apply again.'));
    }
}
