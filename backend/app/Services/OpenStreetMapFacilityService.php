<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenStreetMapFacilityService
{
    private const FRESH_CACHE_MINUTES = 30;

    private const STALE_CACHE_DAYS = 30;

    private const REGIONAL_SNAPSHOTS_KEY = 'osm-facilities:regional-snapshots:v1';

    private const MAX_REGIONAL_SNAPSHOTS = 24;

    /** Independent public mirrors. A single unhealthy Overpass node must not
     * make nearby facilities disappear from the application. */
    private const ENDPOINTS = [
        'https://overpass.private.coffee/api/interpreter',
        'https://overpass-api.de/api/interpreter',
        'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
    ];

    public function nearby(float $latitude, float $longitude, int $radiusMetres = 10000, ?string $facilityType = null, ?string $search = null): array
    {
        return $this->nearbyResult($latitude, $longitude, $radiusMetres, $facilityType, $search)['facilities'];
    }

    /**
     * @return array{facilities: array, available: bool, stale: bool}
     */
    public function nearbyResult(float $latitude, float $longitude, int $radiusMetres = 10000, ?string $facilityType = null, ?string $search = null): array
    {
        $radiusMetres = max(1000, min($radiusMetres, 20000));
        // A roughly 1 km bucket absorbs normal GPS drift and lets nearby users
        // share the same Overpass response instead of creating near-identical
        // expensive requests for every coordinate.
        $cacheKey = sprintf('osm-facilities:v3:%0.2f:%0.2f:%d', $latitude, $longitude, $radiusMetres);

        $cache = Cache::store('file');
        $facilities = $cache->get($cacheKey);
        if (is_array($facilities)) {
            return [
                'facilities' => $this->filter($facilities, $facilityType, $search),
                'available' => true,
                'stale' => false,
            ];
        }

        $available = true;
        $stale = false;
        try {
            $facilities = $this->fetch($latitude, $longitude, $radiusMetres);
            $cache->put($cacheKey, $facilities, now()->addMinutes(self::FRESH_CACHE_MINUTES));
            $cache->put("{$cacheKey}:stale", $facilities, now()->addDays(self::STALE_CACHE_DAYS));
            $this->rememberRegionalSnapshot($latitude, $longitude, $radiusMetres, $facilities);
        } catch (Throwable $e) {
            Log::warning('Unable to load nearby facilities from OpenStreetMap.', ['message' => $e->getMessage()]);
            $facilities = $cache->get("{$cacheKey}:stale");
            if (! is_array($facilities)) {
                $facilities = $this->regionalFallback($latitude, $longitude, $radiusMetres);
            }
            $available = $facilities !== [];
            $stale = true;
        }

        return [
            'facilities' => $this->filter($facilities, $facilityType, $search),
            'available' => $available,
            'stale' => $stale,
        ];
    }

    /**
     * Keep a small rolling collection of successful nearby queries. Unlike the
     * exact request cache, this can still provide partial nearby results after
     * the user moves or their GPS coordinates jitter while Overpass is down.
     */
    private function rememberRegionalSnapshot(float $latitude, float $longitude, int $radiusMetres, array $facilities): void
    {
        if ($facilities === []) {
            return;
        }

        $cache = Cache::store('file');
        $snapshots = $cache->get(self::REGIONAL_SNAPSHOTS_KEY, []);
        $snapshots = is_array($snapshots) ? $snapshots : [];
        $snapshotKey = sprintf('%0.2f:%0.2f:%d', $latitude, $longitude, $radiusMetres);
        $snapshots[$snapshotKey] = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_metres' => $radiusMetres,
            'saved_at' => now()->getTimestamp(),
            'facilities' => $facilities,
        ];

        uasort($snapshots, fn (array $a, array $b) => ($b['saved_at'] ?? 0) <=> ($a['saved_at'] ?? 0));
        $snapshots = array_slice($snapshots, 0, self::MAX_REGIONAL_SNAPSHOTS, true);
        $cache->put(self::REGIONAL_SNAPSHOTS_KEY, $snapshots, now()->addDays(self::STALE_CACHE_DAYS));
    }

    private function regionalFallback(float $latitude, float $longitude, int $radiusMetres): array
    {
        $snapshots = Cache::store('file')->get(self::REGIONAL_SNAPSHOTS_KEY, []);
        if (! is_array($snapshots)) {
            return [];
        }

        $minimumTimestamp = now()->subDays(self::STALE_CACHE_DAYS)->getTimestamp();
        $facilities = [];
        foreach ($snapshots as $snapshot) {
            if (! is_array($snapshot) || ($snapshot['saved_at'] ?? 0) < $minimumTimestamp) {
                continue;
            }

            foreach ($snapshot['facilities'] ?? [] as $facility) {
                if (! is_array($facility) || ! isset($facility['latitude'], $facility['longitude'])) {
                    continue;
                }
                if ($this->distanceMetres($latitude, $longitude, (float) $facility['latitude'], (float) $facility['longitude']) <= $radiusMetres) {
                    $facilities[$facility['facility_id']] = $facility;
                }
            }
        }

        return array_values($facilities);
    }

    private function distanceMetres(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $earthRadiusMetres = 6371000;
        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($longitudeDelta / 2) ** 2;

        return $earthRadiusMetres * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function filter(array $facilities, ?string $facilityType, ?string $search): array
    {
        return array_values(array_filter($facilities, function (array $facility) use ($facilityType, $search): bool {
            if ($facilityType && $facility['facility_type'] !== $facilityType) {
                return false;
            }

            return ! $search || $this->fuzzyContains(implode(' ', array_filter([
                $facility['facility_name'],
                $facility['facility_name_en'],
                $facility['address'],
                $facility['province'],
                $facility['district'],
                $facility['sub_district'],
            ])), $search);
        }));
    }

    private function fuzzyContains(string $text, string $query): bool
    {
        $text = mb_strtolower(trim($text));
        $query = mb_strtolower(trim($query));
        if ($query === '' || mb_stripos($text, $query) !== false) {
            return true;
        }

        $characters = preg_split('//u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($characters) < 3 || count($characters) > 32) {
            return false;
        }

        foreach (array_keys($characters) as $index) {
            $left = implode('', array_slice($characters, 0, $index));
            $right = implode('', array_slice($characters, $index + 1));
            $leftPosition = mb_strpos($text, $left);
            if ($leftPosition === false) {
                continue;
            }
            if (mb_strpos($text, $right, $leftPosition + mb_strlen($left)) !== false) {
                return true;
            }
        }

        return false;
    }

    private function fetch(float $latitude, float $longitude, int $radiusMetres): array
    {
        $query = <<<OVERPASS
[out:json][timeout:20];
(
  nwr(around:{$radiusMetres},{$latitude},{$longitude})["amenity"~"^(hospital|clinic|pharmacy|doctors|dentist|health_post)$"];
  nwr(around:{$radiusMetres},{$latitude},{$longitude})["healthcare"~"^(hospital|clinic|pharmacy|doctor|dentist|physiotherapist|laboratory|midwife|nurse|alternative|optometrist|rehabilitation)$"];
);
out center tags;
OVERPASS;

        // Query independent mirrors concurrently. Laravel waits at most for
        // the slowest request (7 seconds), rather than potentially waiting
        // 7 seconds for each mirror one after another.
        try {
            $responses = Http::pool(function (Pool $pool) use ($query) {
                $requests = [];
                foreach (self::ENDPOINTS as $index => $endpoint) {
                    $requests[] = $pool->as("mirror-{$index}")
                        ->asForm()
                        ->acceptJson()
                        ->withUserAgent('Checkup healthcare facility finder/1.0')
                        ->connectTimeout(3)
                        ->timeout(7)
                        ->post($endpoint, ['data' => $query]);
                }

                return $requests;
            });
        } catch (Throwable) {
            $responses = [];
        }

        $response = null;
        foreach ($responses as $candidate) {
            if (! $candidate instanceof Throwable && $candidate->successful()) {
                $response = $candidate;
                break;
            }
        }
        if ($response === null) {
            throw new \RuntimeException('Every configured Overpass endpoint failed.');
        }

        $facilities = [];
        foreach ($response->json('elements', []) as $element) {
            $facility = $this->normalise($element);
            if ($facility !== null) {
                $facilities[$facility['facility_id']] = $facility;
            }
        }

        return array_values($facilities);
    }

    private function normalise(array $element): ?array
    {
        $tags = $element['tags'] ?? [];
        $latitude = $element['lat'] ?? $element['center']['lat'] ?? null;
        $longitude = $element['lon'] ?? $element['center']['lon'] ?? null;
        $name = $tags['name:th'] ?? $tags['name'] ?? $tags['name:en'] ?? null;
        if ($latitude === null || $longitude === null || ! $name) {
            return null;
        }

        $osmType = $tags['amenity'] ?? $tags['healthcare'] ?? '';
        $facilityType = match ($osmType) {
            'hospital' => 'H',
            'pharmacy' => 'P',
            default => 'C',
        };
        $elementType = $element['type'] ?? 'node';
        $elementId = $element['id'] ?? md5("{$latitude}:{$longitude}:{$name}");

        return [
            'facility_id' => "OSM-{$elementType}-{$elementId}",
            'facility_name' => $name,
            'facility_name_en' => $tags['name:en'] ?? null,
            'facility_type' => $facilityType,
            'address' => $this->address($tags),
            'province' => $tags['addr:province'] ?? null,
            'district' => $tags['addr:district'] ?? $tags['addr:city'] ?? null,
            'sub_district' => $tags['addr:subdistrict'] ?? null,
            'postal_code' => $tags['addr:postcode'] ?? null,
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'phone' => $tags['contact:phone'] ?? $tags['phone'] ?? null,
            'website' => $tags['contact:website'] ?? $tags['website'] ?? null,
            'open_hours' => $tags['opening_hours'] ?? null,
            'source' => 'openstreetmap',
            'osm_url' => "https://www.openstreetmap.org/{$elementType}/{$elementId}",
        ];
    }

    private function address(array $tags): ?string
    {
        $address = implode(' ', array_filter([$tags['addr:housenumber'] ?? null, $tags['addr:street'] ?? null]));

        return $address !== '' ? $address : null;
    }
}
