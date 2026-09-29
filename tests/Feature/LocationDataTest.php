<?php

namespace Tests\Feature;

use App\Enums\ServiceCategory;
use App\Models\Admin;
use App\Models\Location;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationDataTest extends TestCase
{
    use RefreshDatabase;

    private function location(array $attributes = []): Location
    {
        return Location::create(array_merge([
            'name' => 'Penjahit Tes',
            'address' => 'Jl. Tes No. 1',
            'image_url' => 'images/penjahit/tes.jpg',
            'lat' => -6.2,
            'lng' => 106.8,
            'telepon' => '0812-3456-7890',
        ], $attributes));
    }

    /** Waktu di zona WIB. 2026-10-04 adalah hari Minggu. */
    private function at(string $datetime): Carbon
    {
        return Carbon::parse($datetime, Location::TIMEZONE);
    }

    public function test_slug_is_generated_unique_and_stable(): void
    {
        $a = $this->location(['name' => 'Tailor Kebaya Bu Sri']);
        $b = $this->location(['name' => 'Tailor Kebaya Bu Sri']);

        $this->assertSame('tailor-kebaya-bu-sri', $a->slug);
        $this->assertSame('tailor-kebaya-bu-sri-2', $b->slug);

        $a->update(['name' => 'Nama Baru']);
        $this->assertSame('tailor-kebaya-bu-sri', $a->fresh()->slug);
    }

    public function test_cover_url_supports_relative_and_absolute_paths(): void
    {
        $this->assertSame(asset('images/penjahit/tes.jpg'), $this->location()->cover_url);
        $this->assertSame('https://example.com/a.jpg', $this->location(['image_url' => 'https://example.com/a.jpg'])->cover_url);
    }

    public function test_whatsapp_url_normalizes_indonesian_number(): void
    {
        $this->assertSame('https://wa.me/6281234567890?text=Halo%20kak', $this->location()->whatsappUrl('Halo kak'));
        $this->assertNull($this->location(['telepon' => null])->whatsappUrl());
    }

    public function test_opening_status_uses_daily_hours_and_day_off(): void
    {
        $location = $this->location();
        // Minggu libur, Senin–Sabtu 08:00–17:00
        foreach (range(1, 6) as $day) {
            $location->hours()->create(['day_of_week' => $day, 'opens_at' => '08:00', 'closes_at' => '17:00']);
        }
        $location->hours()->create(['day_of_week' => 0]);

        $this->assertFalse($location->isOpenNow($this->at('2026-10-04 10:00')));          // Minggu
        $this->assertSame('Tutup · buka besok 08.00', $location->statusText($this->at('2026-10-04 10:00')));
        $this->assertSame('Buka · tutup 17.00', $location->statusText($this->at('2026-10-05 10:00')));  // Senin
        $this->assertSame('Tutup · buka 08.00', $location->statusText($this->at('2026-10-05 07:00')));
        $this->assertFalse($location->isOpenNow($this->at('2026-10-05 17:00')));          // tepat jam tutup
    }

    public function test_overnight_hours_continue_after_midnight(): void
    {
        $location = $this->location();
        $location->hours()->create(['day_of_week' => 5, 'opens_at' => '22:00', 'closes_at' => '02:00']); // Jumat

        $this->assertTrue($location->isOpenNow($this->at('2026-10-02 23:00')));   // Jumat malam
        $this->assertTrue($location->isOpenNow($this->at('2026-10-03 01:30')));   // Sabtu dini hari
        $this->assertFalse($location->isOpenNow($this->at('2026-10-03 03:00')));
    }

    public function test_services_price_from_and_categories(): void
    {
        $location = $this->location();
        $location->services()->createMany([
            ['category' => 'kebaya', 'name' => 'Kebaya modern', 'price_from' => 150000, 'duration_min_days' => 5, 'duration_max_days' => 7],
            ['category' => 'permak', 'name' => 'Permak', 'price_from' => 25000, 'duration_min_days' => 1, 'duration_max_days' => 1],
        ]);

        $location->load('services');
        $this->assertSame(25000, $location->priceFrom());
        $this->assertEqualsCanonicalizing([ServiceCategory::Kebaya, ServiceCategory::Permak], $location->categories());
        $this->assertSame('1 hari', $location->services->firstWhere('name', 'Permak')->durationText());
    }

    public function test_review_tags_are_validated_and_counted(): void
    {
        $location = $this->location();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/submit-review', [
                'location_id' => $location->id, 'rating' => 5, 'review' => 'Mantap',
                'tags' => ['bukan_tag'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tags.0');

        $this->postJson('/submit-review', [
            'location_id' => $location->id, 'rating' => 5, 'review' => 'Mantap',
            'tags' => ['rapi', 'tepat_waktu', 'rapi'],
        ])->assertOk();

        Review::create(['location_id' => $location->id, 'rating' => 4, 'review' => 'Oke', 'tags' => ['rapi']]);

        $this->assertSame(['rapi', 'tepat_waktu'], Review::first()->tags);
        $this->assertSame(['rapi' => 2, 'tepat_waktu' => 1], $location->reviewTagCounts());

        $reviews = $this->getJson("/reviews/{$location->id}")->assertOk()->json();
        $this->assertSame(['rapi', 'tepat_waktu'], collect($reviews)->firstWhere('review', 'Mantap')['tags']);
    }

    public function test_admin_form_syncs_daily_hours(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password123']);
        $location = $this->location();

        $this->actingAs($admin, 'admin')
            ->put(route('penjahit.update', $location->id), [
                'name' => 'Penjahit Tes',
                'address' => 'Jl. Tes No. 1',
                'image_url' => 'images/penjahit/tes.jpg',
                'lat' => -6.2,
                'lng' => 106.8,
                'opening_hours_start' => '09:00',
                'opening_hours_end' => '18:00',
            ])
            ->assertRedirect(route('datapenjahit'));

        $this->assertSame(7, $location->hours()->count());
        $this->assertSame(7, $location->hours()->where('opens_at', '09:00')->where('closes_at', '18:00')->count());
    }

    public function test_seeder_builds_complete_demo_data(): void
    {
        $this->seed();

        $location = Location::with(['services', 'hours', 'photos'])->where('slug', 'tailor-kebaya-bu-sri')->firstOrFail();

        $this->assertSame(12, Location::count());
        $this->assertCount(7, $location->hours);
        $this->assertNotEmpty($location->services);
        $this->assertNotEmpty($location->photos->first()->credit);
        $this->assertFileExists(public_path($location->image_url));
        $this->assertStringStartsWith('https://wa.me/62800', $location->whatsappUrl());
    }
}
