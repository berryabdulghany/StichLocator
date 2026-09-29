<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoutePreviewTest extends TestCase
{
    private array $query = [
        'from_lat' => -6.2000, 'from_lng' => 106.8166,
        'to_lat' => -6.1895, 'to_lng' => 106.8441,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function fakeOrs(): void
    {
        Http::fake([
            'api.openrouteservice.org/*' => Http::response([
                'features' => [[
                    'geometry' => ['coordinates' => [[106.8166, -6.2], [106.83, -6.195], [106.8441, -6.1895]]],
                    'properties' => ['summary' => ['distance' => 3412.6, 'duration' => 721.3]],
                ]],
            ]),
        ]);
    }

    public function test_returns_503_when_api_key_is_missing(): void
    {
        config(['services.openrouteservice.key' => null]);

        $this->getJson('/rute?' . http_build_query($this->query))->assertStatus(503);
    }

    public function test_returns_route_and_keeps_api_key_on_server(): void
    {
        config(['services.openrouteservice.key' => 'rahasia-123']);
        $this->fakeOrs();

        $this->getJson('/rute?' . http_build_query($this->query + ['mode' => 'walking']))
            ->assertOk()
            ->assertJson(['mode' => 'walking', 'distance' => 3413, 'duration' => 721])
            ->assertJsonPath('coordinates.0', [-6.2, 106.8166])   // dibalik ke [lat, lng]
            ->assertDontSee('rahasia-123');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'rahasia-123')
            && str_contains($request->url(), '/v2/directions/foot-walking')
            && str_contains(urldecode($request->url()), 'start=106.8166,-6.2'));
    }

    public function test_identical_routes_are_cached(): void
    {
        config(['services.openrouteservice.key' => 'rahasia-123']);
        $this->fakeOrs();

        $this->getJson('/rute?' . http_build_query($this->query))->assertOk();
        $this->getJson('/rute?' . http_build_query($this->query))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_upstream_error_returns_502(): void
    {
        config(['services.openrouteservice.key' => 'rahasia-123']);
        Http::fake(['api.openrouteservice.org/*' => Http::response(['error' => 'quota'], 403)]);

        $this->getJson('/rute?' . http_build_query($this->query))->assertStatus(502);
    }

    public function test_rejects_coordinates_outside_indonesia_and_unknown_modes(): void
    {
        config(['services.openrouteservice.key' => 'rahasia-123']);

        $this->getJson('/rute?' . http_build_query(['from_lat' => 48.85, 'from_lng' => 2.35] + $this->query))
            ->assertUnprocessable();
        $this->getJson('/rute?' . http_build_query($this->query + ['mode' => 'helikopter']))
            ->assertUnprocessable();
    }
}
