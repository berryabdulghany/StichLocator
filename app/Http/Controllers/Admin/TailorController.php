<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TailorRequest;
use App\Models\Location;
use App\Support\Activity;
use App\Support\TailorEditor;
use Illuminate\Http\Request;
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
            ->with(['hours', 'account'])
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
            'week' => TailorEditor::emptyWeek(),
            'categories' => ServiceCategory::cases(),
        ]);
    }

    public function store(TailorRequest $request)
    {
        $tailor = TailorEditor::save(new Location(), $request, canPublish: true);

        Activity::log('created', $tailor, 'Added tailor :name', ['name' => $tailor->name]);

        return redirect()->route('admin.tailors.edit', $tailor)
            ->with('status', __('Tailor :name added.', ['name' => $tailor->name]));
    }

    public function edit(Location $tailor)
    {
        $tailor->load(['services', 'hours', 'photos', 'account']);

        return view('admin.tailors.form', [
            'tailor' => $tailor,
            'week' => $tailor->weeklyHours(),
            'categories' => ServiceCategory::cases(),
            'invitation' => $tailor->invitations()->pending()->first(),
        ]);
    }

    public function update(TailorRequest $request, Location $tailor)
    {
        $wasPublished = $tailor->is_published;

        TailorEditor::save($tailor, $request, canPublish: true);

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

    private function filteredQuery(array $filters)
    {
        return Location::query()
            ->when($filters['q'], fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")))
            ->when($filters['status'] === 'published', fn ($query) => $query->where('is_published', true))
            ->when($filters['status'] === 'draft', fn ($query) => $query->where('is_published', false));
    }
}
