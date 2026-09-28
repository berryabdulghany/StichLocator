<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    // Method baru untuk API
    public function getLocations()
    {
        $locations = Location::all();
        return response()->json($locations);
    }

    public function index()
    {
        $locations = Location::all()->each(function ($location) {
            $location->dynamic_status = $location->isOpenNow() ? 'Buka' : 'Tutup';
        });

        return view('admin.datapenjahit', compact('locations'));
    }

    public function store(Request $request)
    {
        Location::create($this->validatedData($request));

        return redirect()->route('datapenjahit')->with('success', 'Data penjahit berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);
        $location->update($this->validatedData($request));

        return redirect()->route('datapenjahit')->with('success', 'Data penjahit berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Location::findOrFail($id)->delete();

        return redirect()->route('datapenjahit')->with('success', 'Data penjahit berhasil dihapus.');
    }

    /**
     * Validasi input form penjahit dan kembalikan hanya kolom yang boleh diisi.
     */
    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'telepon' => 'nullable|string|max:20',
            'image_url' => 'required|url|max:2048',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'opening_hours_start' => 'required|date_format:H:i',
            'opening_hours_end' => 'required|date_format:H:i',
        ]);

        $validated['opening_hours'] = $validated['opening_hours_start'] . ' - ' . $validated['opening_hours_end'];
        unset($validated['opening_hours_start'], $validated['opening_hours_end']);

        return $validated;
    }
}
