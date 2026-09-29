<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_landing_page_shows_stats_categories_and_top_tailors(): void
    {
        $response = $this->get('/')->assertOk();

        $this->assertSame(12, $response->viewData('stats')['tailors']);
        $this->assertCount(4, $response->viewData('topTailors'));

        $kebaya = $response->viewData('categories')->firstWhere('value', 'kebaya');
        $this->assertSame(3, $kebaya['count']);
        $this->assertSame('Rp150rb', $kebaya['price_from']); // "Kebaya modern" termurah di kategori kebaya

        $response
            ->assertSee(route('dashboard', ['kategori' => 'kebaya']), false)
            ->assertSee(route('dashboard', ['dekat' => 1]), false)
            ->assertSee('action="' . route('dashboard') . '"', false)
            ->assertSee('og:title', false);
    }

    public function test_top_tailors_are_sorted_by_rating(): void
    {
        $ratings = $this->get('/')->viewData('topTailors')->pluck('rating')->all();

        $sorted = $ratings;
        rsort($sorted);
        $this->assertSame($sorted, $ratings);
    }

    public function test_map_moved_to_peta_and_logo_links_home(): void
    {
        $this->get('/peta')->assertOk()->assertSee('window.EXPLORER', false);

        $this->get('/penjahit/tailor-kebaya-bu-sri')
            ->assertSee('href="' . route('home') . '"', false)
            ->assertSee(route('dashboard'), false);
    }

    public function test_landing_page_is_translated(): void
    {
        $this->get('/')->assertSee('Cara kerja');

        $this->get('/lang/en');
        $this->get('/')->assertSee('How it works');
    }
}
