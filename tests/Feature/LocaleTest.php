<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_default_language_is_indonesian(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('<html lang="id">', false)
            ->assertSee('Selamat datang kembali');
    }

    public function test_user_can_switch_to_english(): void
    {
        $this->get('/lang/en')->assertSessionHas('locale', 'en');

        $this->get('/login')
            ->assertSee('<html lang="en">', false)
            ->assertSee('Welcome back');
    }

    public function test_unsupported_language_returns_404(): void
    {
        $this->get('/lang/fr')->assertNotFound();
    }

    public function test_switch_redirects_back_to_internal_page_only(): void
    {
        $this->get('/lang/en', ['referer' => url('/profile')])
            ->assertRedirect(url('/profile'));

        $this->get('/lang/en', ['referer' => 'https://situs-jahat.test/phishing'])
            ->assertRedirect(route('dashboard'));
    }
}
