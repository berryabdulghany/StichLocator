<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TailorRequest;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Kelola data penjahit: profil, lokasi, jam buka per hari, layanan & harga, dan foto.
 */
class TailorController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $tailors = Location::query()
            ->with('hours')
            ->withCount(['services', 'photos'])
            ->when($search, fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tailors.index', compact('tailors', 'search'));
    }

    public function create()
    {
        return view('admin.tailors.form', [
            'tailor' => new Location(['lat' => -6.9175, 'lng' => 107.6191]),
            'week' => $this->emptyWeek(),
            'categories' => ServiceCategory::cases(),
        ]);
    }

    public function store(TailorRequest $request)
    {
        $tailor = DB::transaction(fn () => $this->save(new Location(), $request));

        return redirect()->route('admin.tailors.edit', $tailor)
            ->with('status', __('Tailor :name added.', ['name' => $tailor->name]));
    }

    public function edit(Location $tailor)
    {
        $tailor->load(['services', 'hours', 'photos']);

        return view('admin.tailors.form', [
            'tailor' => $tailor,
            'week' => $tailor->weeklyHours(),
            'categories' => ServiceCategory::cases(),
        ]);
    }

    public function update(TailorRequest $request, Location $tailor)
    {
        DB::transaction(fn () => $this->save($tailor, $request));

        return redirect()->route('admin.tailors.edit', $tailor)
            ->with('status', __('Changes saved.'));
    }

    public function destroy(Location $tailor)
    {
        $files = $tailor->photos->pluck('path')->push($tailor->image_url);
        $name = $tailor->name;

        $tailor->delete(); // layanan, jam, foto, dan ulasan ikut terhapus (cascade)
        $files->each(fn ($path) => $this->deleteUploadedFile($path));

        return redirect()->route('admin.tailors.index')->with('status', __('Tailor :name deleted.', ['name' => $name]));
    }

    /*
    |--------------------------------------------------------------------------
    | Simpan semua bagian form dalam satu transaksi
    |--------------------------------------------------------------------------
    */

    private function save(Location $tailor, TailorRequest $request): Location
    {
        $data = $request->validated();
        $week = $data['hours'];

        $tailor->fill([
            'name' => $data['name'],
            'address' => $data['address'],
            'description' => $data['description'] ?? null,
            'telepon' => $data['telepon'] ?? null,
            'offers_home_visit' => $data['offers_home_visit'],
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'opening_hours' => $this->summarizeHours($week),
        ]);

        if ($request->hasFile('cover')) {
            $this->deleteUploadedFile($tailor->image_url);
            $tailor->image_url = 'storage/' . $request->file('cover')->store('tailors', 'public');
        }

        $tailor->save();

        // Jam buka per hari
        foreach ($week as $day => $hours) {
            $tailor->hours()->updateOrCreate(['day_of_week' => $day], [
                'opens_at' => $hours['closed'] ? null : $hours['opens_at'],
                'closes_at' => $hours['closed'] ? null : $hours['closes_at'],
            ]);
        }

        // Layanan: disusun ulang sesuai urutan di form
        $tailor->services()->delete();
        foreach ($data['services'] ?? [] as $order => $service) {
            $tailor->services()->create([...$service, 'sort_order' => $order]);
        }

        // Foto: hapus yang dicentang, lalu tambahkan unggahan baru di akhir galeri
        if (! empty($data['photos_delete'])) {
            $tailor->photos()->whereIn('id', $data['photos_delete'])->get()->each(function ($photo) {
                $this->deleteUploadedFile($photo->path);
                $photo->delete();
            });
        }

        $order = (int) $tailor->photos()->max('sort_order');
        foreach ($request->file('photos', []) as $file) {
            $tailor->photos()->create([
                'path' => 'storage/' . $file->store('tailors/gallery', 'public'),
                'sort_order' => ++$order,
            ]);
        }

        return $tailor->refresh();
    }

    /** Ringkasan jam (kolom opening_hours) dari hari buka pertama, dipakai sebagai cadangan */
    private function summarizeHours(array $week): ?string
    {
        foreach ([1, 2, 3, 4, 5, 6, 0] as $day) {
            if (! $week[$day]['closed']) {
                return $week[$day]['opens_at'] . ' - ' . $week[$day]['closes_at'];
            }
        }

        return null;
    }

    /** Hapus file hanya jika hasil unggahan (bukan foto demo di public/images) */
    private function deleteUploadedFile(?string $path): void
    {
        if ($path && Str::startsWith($path, 'storage/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }

    private function emptyWeek(): array
    {
        // Default: Senin–Sabtu 08:00–17:00, Minggu libur
        return array_map(fn ($day) => $day === 0 ? null : ['opens_at' => '08:00', 'closes_at' => '17:00'], range(0, 6));
    }
}
