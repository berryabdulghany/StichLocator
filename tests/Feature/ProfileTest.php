<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->user = User::factory()->create(['password' => Hash::make('password-lama')]);
        $this->location = Location::create([
            'name' => 'Penjahit Tes',
            'address' => 'Jl. Tes No. 1, Coblong, Kota Bandung',
            'image_url' => 'images/penjahit/tes.jpg',
            'lat' => -6.9,
            'lng' => 107.6,
        ]);
    }

    public function test_profile_lists_my_reviews_and_stats(): void
    {
        Review::create(['user_id' => $this->user->id, 'location_id' => $this->location->id, 'rating' => 4, 'review' => 'Ulasan saya', 'tags' => ['rapi']]);
        Review::create(['user_id' => User::factory()->create()->id, 'location_id' => $this->location->id, 'rating' => 1, 'review' => 'Ulasan orang lain']);

        $response = $this->actingAs($this->user)->get('/profile')->assertOk();

        $response->assertSee('Ulasan saya')
            ->assertDontSee('Ulasan orang lain')
            ->assertSee('Rapi')
            ->assertSee(route('penjahit.show', $this->location->slug), false);

        $this->assertSame(1, $response->viewData('stats')['reviews']);
        $this->assertEquals(4.0, $response->viewData('stats')['average']);
    }

    public function test_user_can_delete_only_their_own_review(): void
    {
        $mine = Review::create(['user_id' => $this->user->id, 'location_id' => $this->location->id, 'rating' => 5, 'review' => 'Mine']);
        $other = Review::create(['user_id' => User::factory()->create()->id, 'location_id' => $this->location->id, 'rating' => 3, 'review' => 'Other']);

        $this->actingAs($this->user)->delete(route('reviews.destroy-own', $other))->assertForbidden();
        $this->assertModelExists($other);

        $this->delete(route('reviews.destroy-own', $mine))
            ->assertRedirect(route('user.profile') . '#ulasan');
        $this->assertModelMissing($mine);

        // Rating & jumlah ulasan penjahit ikut dihitung ulang
        $this->assertSame(1, $this->location->fresh()->reviews);
        $this->assertEquals(3, $this->location->fresh()->rating);
    }

    public function test_password_change_requires_current_password(): void
    {
        $this->actingAs($this->user)
            ->put(route('user.password.update'), [
                'current_password' => 'salah',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('password-lama', $this->user->fresh()->password));

        $this->put(route('user.password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('user.profile') . '#pengaturan');

        $this->assertTrue(Hash::check('password-baru', $this->user->fresh()->password));
    }

    public function test_profile_update_no_longer_changes_password(): void
    {
        $this->actingAs($this->user)->put(route('user.profile.update'), [
            'name' => 'Nama Baru',
            'password' => 'diam-diam-diganti',
            'password_confirmation' => 'diam-diam-diganti',
        ])->assertRedirect(route('user.profile') . '#pengaturan');

        $this->assertSame('Nama Baru', $this->user->fresh()->name);
        $this->assertTrue(Hash::check('password-lama', $this->user->fresh()->password));
    }

    public function test_profile_picture_upload_replaces_old_file(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $this->put(route('user.profile.update'), ['name' => 'A', 'profile_picture' => UploadedFile::fake()->image('a.jpg')]);
        $old = $this->user->fresh()->profile_picture;
        Storage::disk('public')->assertExists($old);

        $this->put(route('user.profile.update'), ['name' => 'A', 'profile_picture' => UploadedFile::fake()->image('b.png')]);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($this->user->fresh()->profile_picture);
    }

    public function test_login_and_register_show_featured_review_panel(): void
    {
        Review::create(['user_id' => $this->user->id, 'location_id' => $this->location->id, 'rating' => 5, 'review' => 'Jahitannya rapi sekali']);

        $this->get('/login')->assertOk()->assertSee('Jahitannya rapi sekali')->assertSee('name="remember"', false);
        $this->get('/register')->assertOk()->assertSee('Jahitannya rapi sekali');
    }
}
