<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Location;
use App\Models\Review;
use App\Models\TailorAccount;
use App\Models\TailorInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Akun mitra penjahit: undangan admin, panel /mitra, balas ulasan, libur sementara, dan statistik.
 */
class TailorPartnerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Location $tailor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');

        $this->admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password123']);
        $this->tailor = Location::create([
            'name' => 'Penjahit Uji', 'address' => 'Jl. Braga No. 1, Sumur Bandung, Kota Bandung',
            'lat' => -6.9175, 'lng' => 107.6094, 'is_published' => true, 'image_url' => 'images/penjahit/kios-penjahit.jpg', 'telepon' => '0800 1111 2222',
        ]);
        foreach (range(0, 6) as $day) {
            $this->tailor->hours()->create(['day_of_week' => $day, 'opens_at' => '00:00', 'closes_at' => '23:59']);
        }
    }

    private function partner(?Location $location = null): TailorAccount
    {
        return TailorAccount::create([
            'location_id' => ($location ?? $this->tailor)->id,
            'name' => 'Pak Uji',
            'email' => 'mitra' . Str::random(4) . '@example.test',
            'password' => 'password123',
        ]);
    }

    /** Link undangan yang ditampilkan ke admin setelah membuat undangan */
    private function inviteAs(string $email): string
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tailors.invite', $this->tailor), ['email' => $email])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        return $response->getSession()->get('invite_link');
    }

    private function profilePayload(array $overrides = []): array
    {
        $hours = [];
        foreach (range(0, 6) as $day) {
            $hours[$day] = ['closed' => '0', 'opens_at' => '08:00', 'closes_at' => '17:00'];
        }

        return array_replace_recursive([
            'name' => 'Penjahit Uji Baru',
            'address' => 'Jl. Braga No. 2, Sumur Bandung, Kota Bandung',
            'telepon' => '0800 1111 3333',
            'lat' => '-6.9175',
            'lng' => '107.6094',
            'hours' => $hours,
            'services' => [
                ['category' => 'permak', 'name' => 'Potong celana', 'price_from' => '20000', 'duration_min_days' => '1', 'duration_max_days' => '1'],
            ],
        ], $overrides);
    }

    /* ---------------------------------------------------------------- Undangan */

    public function test_admin_invites_tailor_and_tailor_joins_with_the_link(): void
    {
        $link = $this->inviteAs('bu.sri@example.test');
        $this->assertStringContainsString('/mitra/undangan/', $link);

        // Token hanya disimpan sebagai hash
        $token = Str::afterLast($link, '/');
        $this->assertDatabaseMissing('tailor_invitations', ['token' => $token]);
        $this->assertDatabaseHas('tailor_invitations', ['token' => hash('sha256', $token), 'email' => 'bu.sri@example.test']);

        auth('admin')->logout();
        $this->get($link)->assertOk()->assertSee('Penjahit Uji')->assertSee('bu.sri@example.test');

        $this->post(route('mitra.invitation.accept', $token), [
            'name' => 'Bu Sri',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('mitra.dashboard'));

        $account = TailorAccount::firstOrFail();
        $this->assertSame($this->tailor->id, $account->location_id);
        $this->assertSame('bu.sri@example.test', $account->email);
        $this->assertAuthenticatedAs($account, 'tailor');
        $this->assertDatabaseHas('activity_logs', ['action' => 'joined', 'tailor_account_id' => $account->id, 'admin_id' => null]);

        // Link hanya bisa dipakai sekali
        auth('tailor')->logout();
        $this->get($link)->assertStatus(410);
    }

    public function test_expired_or_unknown_invitation_links_are_rejected(): void
    {
        [, $token] = TailorInvitation::issue($this->tailor, 'lama@example.test', $this->admin);
        TailorInvitation::query()->update(['expires_at' => now()->subMinute()]);

        $this->get(route('mitra.invitation.show', $token))->assertStatus(410);
        $this->post(route('mitra.invitation.accept', $token), ['name' => 'X', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertStatus(410);
        $this->get(route('mitra.invitation.show', 'token-asal'))->assertStatus(410);
        $this->assertDatabaseCount('tailor_accounts', 0);
    }

    public function test_new_invitation_replaces_the_previous_one(): void
    {
        $first = Str::afterLast($this->inviteAs('a@example.test'), '/');
        $second = Str::afterLast($this->inviteAs('b@example.test'), '/');

        $this->get(route('mitra.invitation.show', $first))->assertStatus(410);
        $this->get(route('mitra.invitation.show', $second))->assertOk();
    }

    public function test_invite_email_cannot_belong_to_another_partner(): void
    {
        $other = Location::create(['name' => 'Lain', 'address' => 'Jl. A, B, C', 'lat' => -6.9, 'lng' => 107.6, 'is_published' => true, 'image_url' => 'images/penjahit/kios-penjahit.jpg']);
        $existing = $this->partner($other);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tailors.invite', $this->tailor), ['email' => $existing->email])
            ->assertSessionHasErrorsIn('invite', 'email');
    }

    public function test_reset_link_sets_a_new_password_for_an_existing_partner(): void
    {
        $account = $this->partner();
        $link = $this->inviteAs($account->email);

        $this->get(route('admin.tailors.edit', $this->tailor))->assertOk()->assertSee('Password reset link');

        auth('admin')->logout();
        $this->post(route('mitra.invitation.accept', Str::afterLast($link, '/')), [
            'name' => 'Pak Uji',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])->assertRedirect(route('mitra.dashboard'));

        $this->assertSame(1, TailorAccount::count());
        $this->assertTrue(Hash::check('passwordbaru1', $account->fresh()->password));
    }

    public function test_admin_can_revoke_partner_access(): void
    {
        $account = $this->partner();

        $this->actingAs($account, 'tailor')->get(route('mitra.dashboard'))->assertOk();

        $this->actingAs($this->admin, 'admin')->delete(route('admin.tailors.account.revoke', $this->tailor))->assertRedirect();
        $this->assertModelMissing($account);
        $this->assertDatabaseHas('activity_logs', ['action' => 'revoked', 'admin_id' => $this->admin->id]);
    }

    /* ---------------------------------------------------------------- Login & akses */

    public function test_partner_login_logout_and_guest_redirect(): void
    {
        $account = $this->partner();

        $this->get(route('mitra.dashboard'))->assertRedirect(route('mitra.login'));

        $this->post(route('mitra.login.submit'), ['email' => $account->email, 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->post(route('mitra.login.submit'), ['email' => $account->email, 'password' => 'password123'])
            ->assertRedirect(route('mitra.dashboard'));
        $this->assertAuthenticatedAs($account, 'tailor');
        $this->assertNotNull($account->fresh()->last_login_at);

        // Akun mitra bukan akun admin
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

        $this->post(route('mitra.logout'))->assertRedirect(route('mitra.login'));
        $this->assertGuest('tailor');
    }

    public function test_every_partner_page_renders(): void
    {
        $this->actingAs($this->partner(), 'tailor');

        foreach ([
            route('mitra.dashboard'),
            route('mitra.profile.edit'),
            route('mitra.reviews.index'),
            route('mitra.reviews.index', ['filter' => 'unreplied']),
            route('mitra.account.edit'),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('Penjahit Uji');
        }
    }

    public function test_partner_is_logged_out_when_their_tailor_is_moved_to_trash(): void
    {
        $this->actingAs($this->partner(), 'tailor');
        $this->tailor->delete();

        $this->get(route('mitra.dashboard'))->assertRedirect(route('mitra.login'));
        $this->assertGuest('tailor');
    }

    /* ---------------------------------------------------------------- Profil */

    public function test_partner_updates_own_profile_but_cannot_change_publish_status(): void
    {
        $account = $this->partner();
        $this->tailor->update(['is_published' => false]);

        $this->actingAs($account, 'tailor')
            ->put(route('mitra.profile.update'), $this->profilePayload(['is_published' => '1']))
            ->assertRedirect(route('mitra.profile.edit'))
            ->assertSessionHasNoErrors();

        $tailor = $this->tailor->fresh(['services']);
        $this->assertSame('Penjahit Uji Baru', $tailor->name);
        $this->assertSame(['Potong celana'], $tailor->services->pluck('name')->all());
        $this->assertFalse($tailor->is_published, 'status terbit tetap diatur admin');

        $log = ActivityLog::where('action', 'updated')->firstOrFail();
        $this->assertSame($account->id, $log->tailor_account_id);
        $this->assertNull($log->admin_id);

        // Mitra boleh melihat pratinjau drafnya sendiri, pengunjung lain tidak
        $this->get(route('penjahit.show', $tailor->slug))->assertOk()->assertSee('Draft preview');
        auth('tailor')->logout();
        $this->get(route('penjahit.show', $tailor->slug))->assertNotFound();
    }

    /* ---------------------------------------------------------------- Balas ulasan */

    public function test_partner_can_reply_to_own_reviews_only(): void
    {
        $account = $this->partner();
        $review = Review::create(['location_id' => $this->tailor->id, 'user_id' => User::factory()->create()->id, 'rating' => 5, 'review' => 'Mantap']);

        $other = Location::create(['name' => 'Lain', 'address' => 'Jl. A, B, C', 'lat' => -6.9, 'lng' => 107.6, 'is_published' => true, 'image_url' => 'images/penjahit/kios-penjahit.jpg']);
        $otherReview = Review::create(['location_id' => $other->id, 'user_id' => User::factory()->create()->id, 'rating' => 1, 'review' => 'Jelek']);

        $this->actingAs($account, 'tailor');

        $this->put(route('mitra.reviews.reply', $otherReview), ['reply' => 'Hmm'])->assertNotFound();
        $this->assertNull($otherReview->fresh()->reply);

        $this->put(route('mitra.reviews.reply', $review), ['reply' => ''])->assertSessionHasErrorsIn("reply{$review->id}", 'reply');
        $this->put(route('mitra.reviews.reply', $review), ['reply' => 'Terima kasih <b>kak</b>!'])->assertRedirect();
        $this->assertSame('Terima kasih <b>kak</b>!', $review->fresh()->reply);
        $this->assertNotNull($review->fresh()->replied_at);

        // Balasan tampil (di-escape) di halaman publik dan JSON ulasan
        $this->get(route('penjahit.show', $this->tailor->slug))
            ->assertSee('Reply from the tailor')
            ->assertSee('Terima kasih &lt;b&gt;kak&lt;/b&gt;!', false);
        $this->getJson(route('reviews.location', $this->tailor->id))->assertJsonFragment(['reply' => 'Terima kasih <b>kak</b>!']);

        $this->delete(route('mitra.reviews.reply.destroy', $review))->assertRedirect();
        $this->assertNull($review->fresh()->reply);
    }

    public function test_admin_can_remove_an_inappropriate_reply(): void
    {
        $review = Review::create([
            'location_id' => $this->tailor->id, 'user_id' => User::factory()->create()->id, 'rating' => 2, 'review' => 'Lama',
            'reply' => 'Balasan kasar', 'replied_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.reviews.update', $review), ['rating' => 2, 'review' => 'Lama', 'reply' => ''])
            ->assertRedirect();

        $this->assertNull($review->fresh()->reply);
        $this->assertNull($review->fresh()->replied_at);
    }

    /* ---------------------------------------------------------------- Libur sementara */

    public function test_temporary_closure_overrides_weekly_hours(): void
    {
        $this->actingAs($this->partner(), 'tailor');
        $this->assertTrue($this->tailor->isOpenNow());

        $this->post(route('mitra.closure.update'), ['closed_until' => now()->subDay()->toDateString()])
            ->assertSessionHasErrorsIn('closure', 'closed_until');

        $until = now(Location::TIMEZONE)->addDays(3)->toDateString();
        $this->post(route('mitra.closure.update'), ['closed_until' => $until, 'closure_note' => 'Mudik'])->assertRedirect();

        $tailor = $this->tailor->fresh();
        $this->assertFalse($tailor->isOpenNow());
        $this->assertSame(
            __('Temporarily closed until :date', ['date' => $tailor->closed_until->translatedFormat('j M')]),
            $tailor->statusText(),
        );
        $this->assertFalse($tailor->toExplorerArray()['is_open']);

        // Setelah tanggalnya lewat, jadwal mingguan berlaku lagi
        $this->assertTrue($tailor->isOpenNow(now(Location::TIMEZONE)->addDays(4)->setTime(12, 0)));

        $this->get(route('penjahit.show', $tailor->slug))->assertSee('Mudik');

        $this->delete(route('mitra.closure.destroy'))->assertRedirect();
        $this->assertNull($this->tailor->fresh()->closed_until);
        $this->assertTrue($this->tailor->fresh()->isOpenNow());
    }

    /* ---------------------------------------------------------------- Statistik */

    public function test_page_views_are_counted_once_per_visitor_per_day(): void
    {
        $url = route('penjahit.show', $this->tailor->slug);

        $this->get($url)->assertOk();
        $this->get($url)->assertOk(); // kunjungan ulang di sesi yang sama tidak dihitung
        $this->assertSame(1, (int) DB::table('location_daily_stats')->value('views'));

        // Bot, admin, dan pemilik tidak dihitung
        $this->flushSession();
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get($url);
        $this->withHeader('User-Agent', 'Mozilla/5.0');
        $this->actingAs($this->admin, 'admin')->get($url);
        auth('admin')->logout();
        $this->flushSession();
        $this->actingAs($this->partner(), 'tailor')->get($url);
        auth('tailor')->logout();
        $this->assertSame(1, (int) DB::table('location_daily_stats')->value('views'));

        // Pengunjung baru (sesi baru) dihitung
        $this->flushSession();
        $this->get($url);
        $this->assertSame(2, (int) DB::table('location_daily_stats')->value('views'));
    }

    public function test_whatsapp_and_route_clicks_are_tracked(): void
    {
        $url = route('penjahit.track', $this->tailor->id);

        $this->post($url, ['metric' => 'whatsapp'])->assertNoContent();
        $this->post($url, ['metric' => 'route'])->assertNoContent();
        $this->post($url, ['metric' => 'whatsapp'])->assertNoContent(); // sesi sama: tidak dihitung lagi
        $this->post($url, ['metric' => 'views'])->assertSessionHasErrors('metric');

        $row = DB::table('location_daily_stats')->first();
        $this->assertSame(1, (int) $row->whatsapp_clicks);
        $this->assertSame(1, (int) $row->route_clicks);
        $this->assertSame(0, (int) $row->views);

        // Draf tidak dihitung
        $this->tailor->update(['is_published' => false]);
        $this->flushSession();
        $this->post($url, ['metric' => 'whatsapp']);
        $this->assertSame(1, (int) DB::table('location_daily_stats')->value('whatsapp_clicks'));

        // Angka muncul di dasbor mitra
        $response = $this->actingAs($this->partner(), 'tailor')->get(route('mitra.dashboard'))->assertOk();
        $this->assertSame(['views' => 0, 'whatsapp' => 1, 'route' => 1], $response->viewData('totals'));
        $this->assertCount(30, $response->viewData('timeline'));
    }

    public function test_seeded_demo_partner_can_log_in(): void
    {
        $this->seed();

        $this->post(route('mitra.login.submit'), ['email' => 'penjahit@stichlocator.test', 'password' => 'password123'])
            ->assertRedirect(route('mitra.dashboard'));

        $response = $this->get(route('mitra.dashboard'))->assertOk()->assertSee('Penjahit Pak Budi');
        $this->assertGreaterThan(0, $response->viewData('totals')['views']);
    }
}
