<?php

namespace Tests\Feature;

use App\Services\OpenStreetMapFacilityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenStreetMapFacilityServiceTest extends TestCase
{
    public function test_it_uses_a_nearby_regional_snapshot_when_every_overpass_endpoint_fails(): void
    {
        $cache = Cache::store('file');
        $cache->forget('osm-facilities:regional-snapshots:v1');
        $cache->forget('osm-facilities:v2:1.234:100.234:10000');
        $cache->forget('osm-facilities:v2:1.240:100.240:10000');

        Http::fake([
            '*' => Http::sequence()
                ->push(['elements' => [[
                    'type' => 'node',
                    'id' => 987654321,
                    'lat' => 1.24,
                    'lon' => 100.24,
                    'tags' => ['amenity' => 'hospital', 'name' => 'Regional Test Hospital'],
                ]]], 200)
                ->pushStatus(503)
                ->pushStatus(503)
                ->pushStatus(503),
        ]);

        $service = app(OpenStreetMapFacilityService::class);
        $fresh = $service->nearbyResult(1.234, 100.234, 10000);
        $fallback = $service->nearbyResult(1.240, 100.240, 10000);

        $this->assertTrue($fresh['available']);
        $this->assertFalse($fresh['stale']);
        $this->assertTrue($fallback['available']);
        $this->assertTrue($fallback['stale']);
        $this->assertSame('Regional Test Hospital', $fallback['facilities'][0]['facility_name']);

        $cache->forget('osm-facilities:regional-snapshots:v1');
        $cache->forget('osm-facilities:v2:1.234:100.234:10000');
        $cache->forget('osm-facilities:v2:1.234:100.234:10000:stale');
        $cache->forget('osm-facilities:v2:1.240:100.240:10000');
        $cache->forget('osm-facilities:v2:1.240:100.240:10000:stale');
    }
}
