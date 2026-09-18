<?php

namespace Tests\Feature;

use App\Services\OpenStreetMapFacilityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenStreetMapFacilityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_it_fetches_endpoints_sequentially_and_caches_a_successful_fallback(): void
    {
        Http::fakeSequence()
            ->pushStatus(503)
            ->push(['elements' => [$this->hospitalElement()]], 200);

        $service = app(OpenStreetMapFacilityService::class);
        $first = $service->nearbyResult(19.9702, 99.8388, 10000);
        $second = $service->nearbyResult(19.9703, 99.8387, 10000);

        $this->assertSame('overpass', $first['source']);
        $this->assertSame('refreshed', $first['cache_status']);
        $this->assertSame('cache', $second['source']);
        $this->assertSame('fresh', $second['cache_status']);
        $this->assertSame('Test Hospital', $second['facilities'][0]['facility_name']);
        Http::assertSentCount(2);
    }

    public function test_it_refreshes_stale_cache_but_returns_it_when_all_endpoints_fail(): void
    {
        Cache::put('healthcare_facilities:v4:19.97:99.84:10000', [
            'facilities' => [$this->normalisedHospital()],
            'fetched_at' => now()->subHours(13)->getTimestamp(),
        ], now()->addDays(7));
        Http::fake(['*' => Http::response([], 503)]);

        $result = app(OpenStreetMapFacilityService::class)->nearbyResult(19.9702, 99.8388, 10000);

        $this->assertTrue($result['available']);
        $this->assertTrue($result['stale']);
        $this->assertSame('stale_cache', $result['source']);
        $this->assertSame('Test Hospital', $result['facilities'][0]['facility_name']);
        Http::assertSentCount(3);
    }

    public function test_it_returns_unavailable_only_when_there_is_no_cache_and_every_endpoint_fails(): void
    {
        Http::fake(['*' => Http::response(['elements' => []], 200)]);

        $result = app(OpenStreetMapFacilityService::class)->nearbyResult(19.9702, 99.8388, 10000);

        $this->assertFalse($result['available']);
        $this->assertTrue($result['stale']);
        $this->assertSame([], $result['facilities']);
        $this->assertSame('miss', $result['cache_status']);
        Http::assertSentCount(3);
    }

    private function hospitalElement(): array
    {
        return [
            'type' => 'node',
            'id' => 987654321,
            'lat' => 19.97,
            'lon' => 99.84,
            'tags' => ['amenity' => 'hospital', 'name' => 'Test Hospital'],
        ];
    }

    private function normalisedHospital(): array
    {
        return [
            'facility_id' => 'OSM-node-987654321',
            'facility_name' => 'Test Hospital',
            'facility_name_en' => null,
            'facility_type' => 'H',
            'address' => null,
            'province' => null,
            'district' => null,
            'sub_district' => null,
            'postal_code' => null,
            'latitude' => 19.97,
            'longitude' => 99.84,
            'phone' => null,
            'website' => null,
            'open_hours' => null,
            'source' => 'openstreetmap',
            'osm_url' => 'https://www.openstreetmap.org/node/987654321',
        ];
    }
}
