<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Location;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;
    private User $user;
    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->user = User::factory()->create();
        $this->location = Location::create([
            'name' => 'Penjahit Tes',
            'address' => 'Jl. Tes No. 1',
            'image_url' => 'https://example.com/a.jpg',
            'lat' => -6.2,
            'lng' => 106.8,
            'opening_hours' => '08:00 - 17:00',
        ]);
        $this->review = Review::create([
            'user_id' => $this->user->id,
            'location_id' => $this->location->id,
            'rating' => 4,
            'review' => 'Bagus',
        ]);
    }

    private function admin(): Admin
    {
        return Admin::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password123']);
    }

    public function test_guest_is_redirected_to_admin_login_from_admin_pages(): void
    {
        foreach (['/admin/dashboard', '/admin/penjahit', '/admin/penjahit/create', '/admin/ulasan', '/admin/pengguna', '/admin/admin'] as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }
    }

    public function test_old_admin_and_public_management_urls_are_gone(): void
    {
        $this->actingAs($this->admin(), 'admin');

        foreach (['/datapenjahit', '/rating_review', '/admin/datapenjahit', '/admin/rating_review', '/admin/users', '/admin/api/reviews'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_guest_and_regular_user_cannot_modify_reviews(): void
    {
        foreach ([null, $this->user] as $actor) {
            if ($actor) {
                $this->actingAs($actor);
            }
            $this->put(route('admin.reviews.update', $this->review), ['rating' => 1, 'review' => 'x'])
                ->assertRedirect(route('admin.login'));
            $this->deleteJson(route('admin.reviews.destroy', $this->review))->assertUnauthorized();
            $this->putJson("/reviews/{$this->review->id}", ['rating' => 1, 'review' => 'x'])
                ->assertStatus(405);
            $this->deleteJson("/reviews/{$this->review->id}")->assertStatus(405);
        }

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'rating' => 4]);
    }

    public function test_regular_user_cannot_manage_tailors(): void
    {
        $this->actingAs($this->user)
            ->delete(route('admin.tailors.destroy', $this->location))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('locations', ['id' => $this->location->id]);
    }

    public function test_review_submission_requires_login_and_valid_data(): void
    {
        $this->postJson('/submit-review', [
            'location_id' => $this->location->id, 'rating' => 5, 'review' => 'x',
        ])->assertUnauthorized();

        $this->actingAs($this->user)
            ->postJson('/submit-review', ['location_id' => $this->location->id, 'rating' => 9, 'review' => ''])
            ->assertUnprocessable();

        $this->postJson('/submit-review', [
            'location_id' => $this->location->id, 'rating' => 5, 'review' => 'Mantap',
        ])->assertOk();

        $location = $this->location->fresh();
        $this->assertSame(2, $location->review_count);
        $this->assertEquals(4.5, $location->rating);
    }

    public function test_public_review_list_does_not_expose_user_email(): void
    {
        $this->getJson("/reviews/{$this->location->id}")
            ->assertOk()
            ->assertJsonMissingPath('0.user')
            ->assertDontSee($this->user->email);
    }

    public function test_admin_registration_closes_once_an_admin_exists(): void
    {
        $this->get(route('admin.register'))->assertOk();

        $this->admin();

        $this->get(route('admin.register'))->assertForbidden();
        $this->post(route('admin.register.submit'), [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();
        $this->assertDatabaseMissing('admins', ['email' => 'penyusup@example.test']);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.process'), ['email' => $this->user->email, 'password' => 'salah']);
        }

        $this->post(route('login.process'), ['email' => $this->user->email, 'password' => 'salah'])
            ->assertStatus(429);
    }

    public function test_location_open_status_handles_normal_and_overnight_hours(): void
    {
        $at = fn (string $time) => Carbon::parse("2026-01-01 {$time}", Location::TIMEZONE);

        $day = new Location(['opening_hours' => '08:00 - 17:00']);
        $this->assertTrue($day->isOpenNow($at('10:00')));
        $this->assertFalse($day->isOpenNow($at('20:00')));

        $night = new Location(['opening_hours' => '22:00 - 02:00']);
        $this->assertTrue($night->isOpenNow($at('23:30')));
        $this->assertTrue($night->isOpenNow($at('01:00')));
        $this->assertFalse($night->isOpenNow($at('12:00')));

        $this->assertFalse((new Location(['opening_hours' => null]))->isOpenNow());
    }
}
