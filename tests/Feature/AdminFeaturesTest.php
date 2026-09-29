<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Location;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fitur admin lanjutan: akun, kompresi foto, draf, galeri, laporan ulasan,
 * ekspor CSV, tempat sampah, dan log aktivitas.
 */
class AdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');

        $this->admin = Admin::create(['name' => 'Admin Utama', 'email' => 'admin@example.test', 'password' => 'password123']);
    }

    private function payload(array $overrides = []): array
    {
        $hours = [];
        foreach (range(0, 6) as $day) {
            $hours[$day] = ['closed' => '0', 'opens_at' => '09:00', 'closes_at' => '18:00'];
        }

        return array_replace_recursive([
            'name' => 'Penjahit Galeri',
            'address' => 'Jl. Braga No. 1, Sumur Bandung, Kota Bandung',
            'lat' => '-6.9175',
            'lng' => '107.6091',
            'cover' => UploadedFile::fake()->image('sampul.jpg', 800, 600),
            'hours' => $hours,
            'services' => [
                ['category' => 'kebaya', 'name' => 'Kebaya', 'price_from' => '150000', 'duration_min_days' => '3', 'duration_max_days' => '5'],
            ],
        ], $overrides);
    }

    private function createTailor(array $overrides = []): Location
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.tailors.store'), $this->payload($overrides))->assertRedirect();

        return Location::latest('id')->firstOrFail();
    }

    private function storedPath(string $publicPath): string
    {
        return Str::after($publicPath, 'storage/');
    }

    /* ---------------------------------------------------------------- Akun */

    public function test_admin_can_update_own_profile(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.account.update'), ['name' => 'Nama Baru', 'email' => 'baru@example.test'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('baru@example.test', $this->admin->fresh()->email);

        // Email admin lain tidak boleh dipakai
        Admin::create(['name' => 'Lain', 'email' => 'lain@example.test', 'password' => 'password123']);
        $this->put(route('admin.account.update'), ['name' => 'X', 'email' => 'lain@example.test'])
            ->assertSessionHasErrorsIn('profile', 'email');
    }

    public function test_admin_password_change_requires_current_password(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.account.password'), [
                'current_password' => 'salah-sekali',
                'password' => 'passwordbaru1',
                'password_confirmation' => 'passwordbaru1',
            ])
            ->assertSessionHasErrorsIn('password', 'current_password');

        $this->put(route('admin.account.password'), [
            'current_password' => 'password123',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('passwordbaru1', $this->admin->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['admin_id' => $this->admin->id, 'action' => 'password']);
    }

    /* ---------------------------------------------------------------- Kompresi foto */

    public function test_uploaded_photos_are_resized_and_converted_to_webp(): void
    {
        $tailor = $this->createTailor([
            'cover' => UploadedFile::fake()->image('besar.jpg', 3200, 2400),
        ]);

        $path = $this->storedPath($tailor->image_url);
        $this->assertStringEndsWith('.webp', $path);

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame(1600, $width);
        $this->assertSame(1200, $height);
    }

    /* ---------------------------------------------------------------- Draf / terbit */

    public function test_new_tailor_is_a_draft_hidden_from_public_but_previewable_by_admin(): void
    {
        $tailor = $this->createTailor(); // tanpa is_published = draf
        $this->assertFalse($tailor->is_published);

        auth('admin')->logout();
        $this->get(route('penjahit.show', $tailor->slug))->assertNotFound();
        $this->getJson(route('locations.get'))->assertOk()->assertJsonMissing(['name' => $tailor->name]);
        $this->getJson(route('reviews.location', $tailor->id))->assertNotFound();

        $this->actingAs($this->admin, 'admin')
            ->get(route('penjahit.show', $tailor->slug))
            ->assertOk()
            ->assertSee('Draft preview', false);

        // Terbitkan
        $this->put(route('admin.tailors.update', $tailor), $this->payload(['is_published' => '1', 'cover' => null]))->assertRedirect();
        $this->assertTrue($tailor->fresh()->is_published);
        $this->assertDatabaseHas('activity_logs', ['action' => 'published', 'subject_id' => $tailor->id]);

        auth('admin')->logout();
        $this->get(route('penjahit.show', $tailor->slug))->assertOk();
    }

    public function test_tailor_list_can_be_filtered_by_status(): void
    {
        $draft = $this->createTailor(['name' => 'Draf Satu']);
        $published = $this->createTailor(['name' => 'Terbit Satu', 'is_published' => '1']);

        $response = $this->get(route('admin.tailors.index', ['status' => 'draft']))->assertOk();
        $this->assertSame([$draft->id], $response->viewData('tailors')->pluck('id')->all());
        $this->assertSame(['all' => 2, 'published' => 1, 'draft' => 1], $response->viewData('counts'));
    }

    /* ---------------------------------------------------------------- Galeri */

    public function test_gallery_can_be_reordered_credited_and_set_as_cover(): void
    {
        $tailor = $this->createTailor([
            'photos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ],
        ]);
        [$a, $b, $c] = $tailor->photos->all();
        $oldCover = $this->storedPath($tailor->image_url);

        $this->put(route('admin.tailors.update', $tailor), $this->payload([
            'cover' => null,
            'photo_order' => [$c->id, $a->id, $b->id],
            'photo_credit' => [$c->id => 'Foto: Budi', $a->id => ''],
            'cover_photo_id' => $c->id,
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $tailor->refresh();
        $this->assertSame([$c->id, $a->id, $b->id], $tailor->photos->pluck('id')->all());
        $this->assertSame('Foto: Budi', $c->fresh()->credit);
        $this->assertNull($a->fresh()->credit);
        $this->assertSame($c->path, $tailor->image_url);
        Storage::disk('public')->assertMissing($oldCover);

        // Foto yang sedang jadi sampul dihapus dari galeri: file tetap ada karena masih dipakai sampul
        $this->put(route('admin.tailors.update', $tailor), $this->payload([
            'cover' => null,
            'photos_delete' => [$c->id],
        ]))->assertRedirect();
        $this->assertModelMissing($c);
        Storage::disk('public')->assertExists($this->storedPath($c->path));
    }

    public function test_gallery_upload_is_limited_to_five_photos(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tailors.store'), $this->payload([
                'photos' => array_map(fn ($i) => UploadedFile::fake()->image("{$i}.jpg"), range(1, 6)),
            ]))
            ->assertSessionHasErrors('photos');
    }

    /* ---------------------------------------------------------------- Laporan ulasan */

    public function test_users_can_report_reviews_and_admin_can_handle_them(): void
    {
        $tailor = $this->createTailor(['is_published' => '1']);
        $author = User::factory()->create();
        $reporter = User::factory()->create();
        $review = Review::create(['location_id' => $tailor->id, 'user_id' => $author->id, 'rating' => 1, 'review' => 'Beli di toko saya saja!']);

        auth('admin')->logout();

        // Tamu tidak bisa melapor; penulis tidak bisa melaporkan ulasannya sendiri
        $this->postJson(route('reviews.report', $review), ['reason' => 'spam'])->assertUnauthorized();
        $this->actingAs($author)->postJson(route('reviews.report', $review), ['reason' => 'spam'])->assertForbidden();
        $this->actingAs($reporter)->postJson(route('reviews.report', $review), ['reason' => 'bukan-alasan'])->assertUnprocessable();

        $this->actingAs($reporter)->postJson(route('reviews.report', $review), ['reason' => 'spam', 'note' => 'Iklan'])->assertOk();
        // Melapor ulang tidak menggandakan laporan
        $this->actingAs($reporter)->postJson(route('reviews.report', $review), ['reason' => 'fake'])->assertOk();
        $this->assertSame(1, ReviewReport::count());

        // Admin melihat badge & tab "Dilaporkan"
        $this->actingAs($this->admin, 'admin');
        $response = $this->get(route('admin.reviews.index', ['reported' => 1]))->assertOk();
        $this->assertSame(1, $response->viewData('reportedCount'));
        $response->assertSee('Looks fake');

        // Abaikan laporan: ulasan tetap ada
        $this->post(route('admin.reviews.dismiss-reports', $review))->assertRedirect();
        $this->assertNotNull(ReviewReport::first()->resolved_at);
        $this->assertSame(0, $this->get(route('admin.reviews.index'))->viewData('reportedCount'));
        $this->assertNotSoftDeleted($review);

        // Laporan baru lalu ulasan dihapus: laporan ikut selesai
        $this->actingAs($reporter)->postJson(route('reviews.report', $review), ['reason' => 'spam'])->assertOk();
        $this->actingAs($this->admin, 'admin')->delete(route('admin.reviews.destroy', $review))->assertRedirect();
        $this->assertSoftDeleted($review);
        $this->assertSame(0, ReviewReport::open()->count());
    }

    /* ---------------------------------------------------------------- Ekspor CSV */

    public function test_tailors_and_reviews_can_be_exported_to_csv(): void
    {
        $tailor = $this->createTailor(['name' => '=HYPERLINK("http://jahat.test")', 'is_published' => '1']);
        Review::create(['location_id' => $tailor->id, 'user_id' => User::factory()->create()->id, 'rating' => 4, 'review' => '@SUM(1+1) rapi']);

        $response = $this->get(route('admin.tailors.export'))->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('"\'=HYPERLINK(""http://jahat.test"")"', $csv, 'rumus dinetralkan dengan tanda kutip tunggal');
        $this->assertStringContainsString('-6.9175', $csv, 'angka negatif tidak ikut diberi tanda kutip');

        $csv = $this->get(route('admin.reviews.export', ['rating' => 4]))->assertOk()->streamedContent();
        $this->assertStringContainsString("'@SUM(1+1) rapi", $csv);
        $this->assertSame(2, substr_count(trim($csv), "\n") + 1, 'header + 1 ulasan');

        $this->assertSame(2, ActivityLog::where('action', 'exported')->count());
    }

    /* ---------------------------------------------------------------- Sampah & prune */

    public function test_trashed_reviews_can_be_restored_and_ratings_follow(): void
    {
        $tailor = $this->createTailor(['is_published' => '1']);
        $review = Review::create(['location_id' => $tailor->id, 'user_id' => User::factory()->create()->id, 'rating' => 2, 'review' => 'Kurang']);
        $tailor->refreshRatingStats();

        $this->delete(route('admin.reviews.destroy', $review));
        $this->assertSame(0, $tailor->fresh()->review_count);

        $this->get(route('admin.trash.index'))->assertOk()->assertSee('Kurang');

        $this->post(route('admin.trash.reviews.restore', $review))->assertRedirect();
        $this->assertNotSoftDeleted($review);
        $this->assertSame(1, $tailor->fresh()->review_count);

        $review->delete();
        $this->delete(route('admin.trash.reviews.destroy', $review))->assertRedirect();
        $this->assertModelMissing($review);
        $this->assertDatabaseHas('activity_logs', ['action' => 'force_deleted']);
    }

    public function test_items_older_than_30_days_in_trash_are_pruned(): void
    {
        $old = $this->createTailor(['name' => 'Lama']);
        $recent = $this->createTailor(['name' => 'Baru']);
        $cover = $this->storedPath($old->image_url);

        $old->delete();
        $recent->delete();
        Location::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subDays(31)]);

        $this->artisan('model:prune', ['--model' => [Location::class, Review::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertSoftDeleted($recent);
        Storage::disk('public')->assertMissing($cover);
    }

    /* ---------------------------------------------------------------- Log aktivitas */

    public function test_admin_actions_are_logged_and_filterable(): void
    {
        $tailor = $this->createTailor(['name' => 'Penjahit Log']);
        $this->delete(route('admin.tailors.destroy', $tailor));

        $this->assertDatabaseHas('activity_logs', ['admin_id' => $this->admin->id, 'action' => 'created', 'subject_type' => 'tailor', 'subject_id' => $tailor->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'subject_id' => $tailor->id]);

        $response = $this->get(route('admin.activity.index', ['action' => 'deleted']))->assertOk();
        $this->assertCount(1, $response->viewData('logs'));
        $response->assertSee('Penjahit Log');
    }
}
