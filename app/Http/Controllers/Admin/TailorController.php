<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TailorRequest;
use App\Models\Location;
use App\Support\Activity;
use App\Support\ImageOptimizer;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kelola data penjahit: profil, status terbit, lokasi, jam buka per hari,
 * layanan & harga, sampul, dan galeri foto.
 */
class TailorController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'status' => in_array($request->query('status'), ['published', 'draft'], true) ? $request->query('status') : null,
        ];

        $tailors = $this->filteredQuery($filters)
            ->with('hours')
            ->withCount(['services', 'photos'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => Location::count(),
            'published' => Location::published()->count(),
            'draft' => Location::where('is_published', false)->count(),
        ];

        return view('admin.tailors.index', compact('tailors', 'filters', 'counts'));
    }

    public function create()
    {
        return view('admin.tailors.form', [
            'tailor' => new Location(['lat' => -6.9175, 'lng' => 107.6191, 'is_published' => false]),
            'week' => $this->emptyWeek(),
            'categories' => ServiceCategory::cases(),
        ]);
    }

    public function store(TailorRequest $request)
    {
        $tailor = DB::transaction(fn () => $this->save(new Location(), $request));

        Activity::log('created', $tailor, 'Added tailor :name', ['name' => $tailor->name]);

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
        $wasPublished = $tailor->is_published;

        DB::transaction(fn () => $this->save($tailor, $request));

        Activity::log('updated', $tailor, 'Updated tailor :name', ['name' => $tailor->name]);

        if ($wasPublished !== $tailor->is_published) {
            Activity::log(
                $tailor->is_published ? 'published' : 'unpublished',
                $tailor,
                $tailor->is_published ? 'Published tailor :name' : 'Moved tailor :name to draft',
                ['name' => $tailor->name],
            );
        }

        return redirect()->route('admin.tailors.edit', $tailor)->with('status', __('Changes saved.'));
    }

    /**
     * Pindahkan ke tempat sampah (bisa dipulihkan selama 30 hari).
     */
    public function destroy(Location $tailor)
    {
        $tailor->delete();

        Activity::log('deleted', $tailor, 'Moved tailor :name to trash', ['name' => $tailor->name]);

        return redirect()->route('admin.tailors.index')
            ->with('status', __('Tailor :name moved to trash.', ['name' => $tailor->name]));
    }

    /**
     * Ekspor daftar penjahit (mengikuti filter yang sedang aktif) ke CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'status' => $request->query('status'),
        ];
        $tailors = $this->filteredQuery($filters)->with('services')->orderBy('name')->get();

        Activity::log('exported', null, 'Exported :count tailors to CSV', ['count' => $tailors->count()]);

        return CsvExport::download('penjahit-' . now()->format('Y-m-d') . '.csv', [
            'ID', 'Nama', 'Slug', 'Alamat', 'Wilayah', 'Telepon', 'Latitude', 'Longitude',
            'Status', 'Rating', 'Jumlah ulasan', 'Kategori', 'Harga mulai', 'Panggilan ukur',
        ], $tailors->map(fn (Location $tailor) => [
            $tailor->id,
            $tailor->name,
            $tailor->slug,
            $tailor->address,
            $tailor->area(),
            $tailor->telepon,
            $tailor->lat,
            $tailor->lng,
            $tailor->is_published ? 'Terbit' : 'Draf',
            $tailor->rating,
            $tailor->review_count,
            collect($tailor->categories())->map->value->implode(', '),
            $tailor->priceFrom(),
            $tailor->offers_home_visit ? 'Ya' : 'Tidak',
        ]));
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
            'is_published' => $data['is_published'],
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'opening_hours' => $this->summarizeHours($week),
        ]);

        $oldCover = $tailor->image_url;
        if ($request->hasFile('cover')) {
            $tailor->image_url = 'storage/' . ImageOptimizer::store($request->file('cover'), 'tailors');
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

        $this->saveGallery($tailor, $request, $data);

        // Sampul lama dihapus jika sudah diganti dan tidak dipakai lagi oleh foto galeri
        if ($oldCover !== $tailor->image_url && ! $tailor->photos()->where('path', $oldCover)->exists()) {
            Uploads::delete($oldCover);
        }

        return $tailor->refresh();
    }

    /**
     * Galeri: hapus yang dicentang, perbarui kredit & urutan, tambah unggahan baru,
     * dan jadikan salah satu foto sebagai sampul.
     */
    private function saveGallery(Location $tailor, TailorRequest $request, array $data): void
    {
        $deleteIds = array_map('intval', $data['photos_delete'] ?? []);

        foreach ($tailor->photos()->whereIn('id', $deleteIds)->get() as $photo) {
            // File tetap disimpan jika foto ini sedang dipakai sebagai sampul
            if ($photo->path !== $tailor->image_url) {
                Uploads::delete($photo->path);
            }
            $photo->delete();
        }

        foreach ($data['photo_credit'] ?? [] as $id => $credit) {
            $tailor->photos()->whereKey($id)->update(['credit' => filled($credit) ? $credit : null]);
        }

        // Urutan sesuai susunan di form (tombol naik/turun)
        foreach (array_values(array_diff(array_map('intval', $data['photo_order'] ?? []), $deleteIds)) as $position => $id) {
            $tailor->photos()->whereKey($id)->update(['sort_order' => $position + 1]);
        }

        $order = (int) $tailor->photos()->max('sort_order');
        foreach ($request->file('photos', []) as $file) {
            $tailor->photos()->create([
                'path' => 'storage/' . ImageOptimizer::store($file, 'tailors/gallery'),
                'sort_order' => ++$order,
            ]);
        }

        // "Jadikan sampul" (diabaikan jika admin juga mengunggah sampul baru)
        $coverPhotoId = $data['cover_photo_id'] ?? null;
        if ($coverPhotoId && ! $request->hasFile('cover') && ! in_array((int) $coverPhotoId, $deleteIds, true)) {
            $photo = $tailor->photos()->find($coverPhotoId);
            if ($photo) {
                $tailor->forceFill(['image_url' => $photo->path])->save();
            }
        }
    }

    private function filteredQuery(array $filters)
    {
        return Location::query()
            ->when($filters['q'], fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")))
            ->when($filters['status'] === 'published', fn ($query) => $query->where('is_published', true))
            ->when($filters['status'] === 'draft', fn ($query) => $query->where('is_published', false));
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

    private function emptyWeek(): array
    {
        // Default: Senin–Sabtu 08:00–17:00, Minggu libur
        return array_map(fn ($day) => $day === 0 ? null : ['opens_at' => '08:00', 'closes_at' => '17:00'], range(0, 6));
    }
}
