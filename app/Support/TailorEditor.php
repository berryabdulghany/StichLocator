<?php

namespace App\Support;

use App\Http\Requests\Admin\TailorRequest;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan form profil penjahit (info, lokasi, jam buka, layanan, sampul, galeri, libur sementara).
 * Dipakai bersama oleh panel admin dan panel mitra penjahit.
 */
class TailorEditor
{
    /**
     * @param  bool  $canPublish  hanya admin yang boleh mengubah status terbit/draf
     */
    public static function save(Location $tailor, TailorRequest $request, bool $canPublish): Location
    {
        return DB::transaction(function () use ($tailor, $request, $canPublish) {
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
                'opening_hours' => self::summarizeHours($week),
                'closed_until' => $data['closed_until'] ?? null,
                'closure_note' => filled($data['closed_until'] ?? null) ? ($data['closure_note'] ?? null) : null,
            ]);

            if ($canPublish) {
                $tailor->is_published = $data['is_published'];
            }

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

            self::saveGallery($tailor, $request, $data);

            // Sampul lama dihapus jika sudah diganti dan tidak dipakai lagi oleh foto galeri
            if ($oldCover !== $tailor->image_url && ! $tailor->photos()->where('path', $oldCover)->exists()) {
                Uploads::delete($oldCover);
            }

            return $tailor->refresh();
        });
    }

    /** Default form penjahit baru: Senin–Sabtu 08:00–17:00, Minggu libur */
    public static function emptyWeek(): array
    {
        return array_map(fn ($day) => $day === 0 ? null : ['opens_at' => '08:00', 'closes_at' => '17:00'], range(0, 6));
    }

    /**
     * Galeri: hapus yang dicentang, perbarui kredit & urutan, tambah unggahan baru,
     * dan jadikan salah satu foto sebagai sampul.
     */
    private static function saveGallery(Location $tailor, TailorRequest $request, array $data): void
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

        // "Jadikan sampul" (diabaikan jika sampul baru juga diunggah)
        $coverPhotoId = $data['cover_photo_id'] ?? null;
        if ($coverPhotoId && ! $request->hasFile('cover') && ! in_array((int) $coverPhotoId, $deleteIds, true)) {
            $photo = $tailor->photos()->find($coverPhotoId);
            if ($photo) {
                $tailor->forceFill(['image_url' => $photo->path])->save();
            }
        }
    }

    /** Ringkasan jam (kolom opening_hours) dari hari buka pertama, dipakai sebagai cadangan */
    private static function summarizeHours(array $week): ?string
    {
        foreach ([1, 2, 3, 4, 5, 6, 0] as $day) {
            if (! $week[$day]['closed']) {
                return $week[$day]['opens_at'] . ' - ' . $week[$day]['closes_at'];
            }
        }

        return null;
    }
}
