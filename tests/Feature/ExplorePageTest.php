<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExplorePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_explore_page_sends_tailor_summaries_to_javascript(): void
    {
        $response = $this->get('/')->assertOk();

        $tailors = $response->viewData('tailors');
        $this->assertCount(12, $tailors);

        $kebaya = $tailors->firstWhere('slug', 'tailor-kebaya-bu-sri');
        $this->assertSame('Jakarta Pusat', $kebaya['area']);
        $this->assertContains('kebaya', $kebaya['categories']);
        $this->assertSame('Rp30rb', $kebaya['price_from_text']);
        $this->assertSame(route('penjahit.show', 'tailor-kebaya-bu-sri'), $kebaya['detail_url']);
        $this->assertStringStartsWith('https://wa.me/62800', $kebaya['whatsapp_url']);

        $response->assertSee('data-category="kebaya"', false);
    }

    public function test_detail_page_renders_full_page_for_shared_links(): void
    {
        $this->get('/penjahit/tailor-kebaya-bu-sri')
            ->assertOk()
            ->assertSee('<html', false)
            ->assertSee('Tailor Kebaya Bu Sri')
            ->assertSee('Kebaya brokat + furing')
            ->assertSee('Rp250rb')
            ->assertSee('id="mini-map"', false)
            ->assertSee('og:title', false);
    }

    public function test_detail_returns_partial_html_for_drawer(): void
    {
        $this->get('/penjahit/tailor-kebaya-bu-sri', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('<html', false)
            ->assertSee('data-detail', false)
            ->assertSee('Layanan dan harga');
    }

    public function test_detail_shows_review_form_only_for_logged_in_users(): void
    {
        $this->get('/penjahit/tailor-kebaya-bu-sri')->assertDontSee('data-review-form', false);

        $this->actingAs(User::first())
            ->get('/penjahit/tailor-kebaya-bu-sri')
            ->assertSee('data-review-form', false)
            ->assertSee('name="tags[]"', false);
    }

    public function test_unknown_tailor_returns_404(): void
    {
        $this->get('/penjahit/tidak-ada')->assertNotFound();
    }

    public function test_user_content_is_escaped_in_detail(): void
    {
        $location = Location::first();
        $location->reviews()->create(['rating' => 5, 'review' => '<script>alert(1)</script>']);

        $this->get('/penjahit/' . $location->slug)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
