<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Location;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');

        $this->admin = Admin::create(['name' => 'Admin Utama', 'email' => 'admin@example.test', 'password' => 'password123']);
        $this->actingAs($this->admin, 'admin');
    }

    /** Data form penjahit yang valid; bisa ditimpa per tes */
    private function tailorPayload(array $overrides = []): array
    {
        $hours = [];
        foreach (range(0, 6) as $day) {
            $hours[$day] = $day === 0
                ? ['closed' => '1']
                : ['closed' => '0', 'opens_at' => '09:00', 'closes_at' => '18:00'];
        }

        return array_replace_recursive([
            'name' => 'Penjahit Admin',
            'address' => 'Jl. Dago No. 1, Coblong, Kota Bandung',
            'description' => 'Deskripsi singkat',
            'telepon' => '0812 1111 2222',
            'offers_home_visit' => '1',
            'lat' => '-6.8856',
            'lng' => '107.6135',
            'cover' => UploadedFile::fake()->image('sampul.jpg', 800, 600),
            'hours' => $hours,
            'services' => [
                ['category' => 'kebaya', 'name' => 'Kebaya modern', 'price_from' => '150000', 'duration_min_days' => '5', 'duration_max_days' => '7'],
                ['category' => 'permak', 'name' => 'Potong celana', 'price_from' => '20000', 'duration_min_days' => '1', 'duration_max_days' => '1'],
            ],
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ], $overrides);
    }

    private function storedPath(string $publicPath): string
    {
        return Str::after($publicPath, 'storage/');
    }

    public function test_every_admin_page_renders(): void
    {
        $this->seed();
        $tailor = Location::first();
        $review = Review::first();

        foreach ([
            route('admin.dashboard'),
            route('admin.tailors.index'),
            route('admin.tailors.index', ['q' => 'kebaya']),
            route('admin.tailors.create'),
            route('admin.tailors.edit', $tailor),
            route('admin.reviews.index', ['rating' => 5, 'tailor' => $tailor->id, 'q' => 'rapi']),
            route('admin.reviews.edit', $review),
            route('admin.users.index', ['q' => 'demo']),
            route('admin.admins.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_dashboard_shows_stats_and_30_day_timeline(): void
    {
        $this->seed();

        $response = $this->get(route('admin.dashboard'))->assertOk();

        $this->assertSame(12, $response->viewData('stats')['tailors']);
        $this->assertSame(Review::count(), $response->viewData('stats')['reviews']);
        $this->assertCount(30, $response->viewData('timeline'));
        $this->assertSame(Review::count(), $response->viewData('distribution')->sum());
    }

    public function test_admin_can_create_tailor_with_hours_services_and_photos(): void
    {
        $response = $this->post(route('admin.tailors.store'), $this->tailorPayload());

        $tailor = Location::where('name', 'Penjahit Admin')->firstOrFail();
        $response->assertRedirect(route('admin.tailors.edit', $tailor));

        $this->assertSame('penjahit-admin', $tailor->slug);
        $this->assertTrue($tailor->offers_home_visit);
        $this->assertSame('09:00 - 18:00', $tailor->opening_hours);
        Storage::disk('public')->assertExists($this->storedPath($tailor->image_url));

        // Minggu libur, hari lain 09:00–18:00
        $week = $tailor->weeklyHours();
        $this->assertNull($week[0]);
        $this->assertSame(['opens_at' => '09:00', 'closes_at' => '18:00'], $week[1]);

        $this->assertSame(['Kebaya modern', 'Potong celana'], $tailor->services->pluck('name')->all());
        $this->assertCount(2, $tailor->photos);
        $tailor->photos->each(fn ($photo) => Storage::disk('public')->assertExists($this->storedPath($photo->path)));
    }

    public function test_tailor_form_is_validated(): void
    {
        $payload = $this->tailorPayload([
            'services' => [0 => ['duration_min_days' => '7', 'duration_max_days' => '3']],
            'hours' => [2 => ['opens_at' => null]],
        ]);
        unset($payload['cover']);

        $this->post(route('admin.tailors.store'), $payload)
            ->assertSessionHasErrors(['cover', 'services.0.duration_max_days', 'hours.2.opens_at']);

        $this->assertDatabaseCount('locations', 0);
    }

    public function test_admin_can_update_tailor_replace_cover_and_delete_photos(): void
    {
        $this->post(route('admin.tailors.store'), $this->tailorPayload());
        $tailor = Location::firstOrFail();
        $oldCover = $this->storedPath($tailor->image_url);
        $photoToDelete = $tailor->photos->first();

        $payload = $this->tailorPayload([
            'name' => 'Nama Baru',
            'cover' => UploadedFile::fake()->image('baru.png'),
            'hours' => [6 => ['closed' => '1']],
            'photos_delete' => [$photoToDelete->id],
        ]);
        // Diganti langsung (bukan digabung) supaya daftar layanan & foto benar-benar baru
        $payload['services'] = [['category' => 'jas', 'name' => 'Jas formal', 'price_from' => '900000', 'duration_min_days' => '14', 'duration_max_days' => '21']];
        $payload['photos'] = [];

        $this->put(route('admin.tailors.update', $tailor), $payload)
            ->assertRedirect(route('admin.tailors.edit', $tailor));

        $tailor->refresh()->load(['services', 'photos', 'hours']);

        $this->assertSame('Nama Baru', $tailor->name);
        $this->assertSame('penjahit-admin', $tailor->slug, 'slug tidak berubah saat nama diganti');
        Storage::disk('public')->assertMissing($oldCover);
        Storage::disk('public')->assertExists($this->storedPath($tailor->image_url));
        Storage::disk('public')->assertMissing($this->storedPath($photoToDelete->path));
        $this->assertCount(1, $tailor->photos);
        $this->assertSame(['Jas formal'], $tailor->services->pluck('name')->all());
        $this->assertNull($tailor->weeklyHours()[6]);
    }

    public function test_deleting_tailor_removes_uploaded_files_and_reviews(): void
    {
        $this->post(route('admin.tailors.store'), $this->tailorPayload());
        $tailor = Location::with('photos')->firstOrFail();
        Review::create(['location_id' => $tailor->id, 'user_id' => User::factory()->create()->id, 'rating' => 5, 'review' => 'Bagus']);
        $files = $tailor->photos->pluck('path')->push($tailor->image_url)->map(fn ($p) => $this->storedPath($p));

        $this->delete(route('admin.tailors.destroy', $tailor))->assertRedirect(route('admin.tailors.index'));

        $this->assertModelMissing($tailor);
        $this->assertDatabaseCount('reviews', 0);
        $files->each(fn ($path) => Storage::disk('public')->assertMissing($path));
    }

    public function test_demo_photos_are_not_deleted_from_public_folder(): void
    {
        $this->seed();
        $tailor = Location::firstOrFail();
        $cover = public_path($tailor->image_url);

        $this->delete(route('admin.tailors.destroy', $tailor));

        $this->assertFileExists($cover);
    }

    public function test_admin_can_moderate_reviews(): void
    {
        $this->seed();
        $review = Review::with('location')->firstOrFail();
        $tailor = $review->location;

        $this->put(route('admin.reviews.update', $review), [
            'rating' => 1,
            'review' => 'Diedit admin',
            'tags' => ['ramah'],
        ])->assertRedirect(route('admin.reviews.index'));

        $review->refresh();
        $this->assertSame('Diedit admin', $review->review);
        $this->assertSame(['ramah'], $review->tags);
        $this->assertEquals(round($tailor->reviews()->avg('rating'), 1), $tailor->fresh()->rating);

        $countBefore = $tailor->fresh()->review_count;
        $this->delete(route('admin.reviews.destroy', $review));
        $this->assertModelMissing($review);
        $this->assertSame($countBefore - 1, $tailor->fresh()->review_count);
    }

    public function test_review_filters(): void
    {
        $this->seed();

        $fiveStars = $this->get(route('admin.reviews.index', ['rating' => 5]))->viewData('reviews');
        $this->assertTrue($fiveStars->every(fn ($review) => $review->rating === 5));

        $tailor = Location::firstOrFail();
        $forTailor = $this->get(route('admin.reviews.index', ['tailor' => $tailor->id]))->viewData('reviews');
        $this->assertSame($tailor->reviews()->count(), $forTailor->total());
    }

    public function test_deleting_user_removes_their_reviews_and_updates_ratings(): void
    {
        $this->seed();
        $user = User::where('email', 'user@stichlocator.test')->firstOrFail();
        $tailorIds = $user->reviews()->pluck('location_id')->unique();

        $this->delete(route('admin.users.destroy', $user))->assertRedirect();

        $this->assertModelMissing($user);
        $this->assertSame(0, Review::where('user_id', $user->id)->count());
        foreach (Location::whereIn('id', $tailorIds)->get() as $tailor) {
            $this->assertSame($tailor->reviews()->count(), $tailor->review_count);
        }
    }

    public function test_admin_accounts_can_be_added_but_not_self_deleted(): void
    {
        $this->post(route('admin.admins.store'), [
            'name' => 'Admin Kedua',
            'email' => 'kedua@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $second = Admin::where('email', 'kedua@example.test')->firstOrFail();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password123', $second->password));

        $this->delete(route('admin.admins.destroy', $this->admin))->assertSessionHasErrors('admin');
        $this->assertModelExists($this->admin);

        $this->delete(route('admin.admins.destroy', $second))->assertRedirect();
        $this->assertModelMissing($second);
    }

    public function test_admin_login_and_logout(): void
    {
        auth('admin')->logout();

        $this->get(route('admin.login'))->assertOk()->assertDontSee(route('admin.register'));

        $this->post(route('admin.login.submit'), ['email' => 'admin@example.test', 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->post(route('admin.login.submit'), ['email' => 'admin@example.test', 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }
}
