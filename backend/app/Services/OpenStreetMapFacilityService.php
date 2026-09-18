<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenStreetMapFacilityService
{
    private const FRESH_CACHE_HOURS = 12;

    private const STALE_CACHE_DAYS = 7;

    private const CACHE_VERSION = 'v4';

    private const OVERPASS_CONNECT_TIMEOUT_SECONDS = 2;

    private const OVERPASS_TIMEOUT_SECONDS = 4;

    private const LOCK_SECONDS = 20;

    private const LOCK_WAIT_SECONDS = 10;

    private const ENDPOINTS = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
        'https://overpass.private.coffee/api/interpreter',
    ];

    public function nearby(float $latitude, float $longitude, int $radiusMetres = 10000, ?string $facilityType = null, ?string $search = null): array
    {
        return $this->nearbyResult($latitude, $longitude, $radiusMetres, $facilityType, $search)['facilities'];
    }

    /** @return array{facilities: array, available: bool, stale: bool, source: string, cache_status: string} */
    public function nearbyResult(float $latitude, float $longitude, int $radiusMetres = 10000, ?string $facilityType = null, ?string $search = null): array
    {
        $radiusMetres = max(1000, min($radiusMetres, 20000));
        $cacheKey = $this->cacheKey($latitude, $longitude, $radiusMetres);
        $cached = $this->readCache($cacheKey);

        if ($cached !== null && $this->isFresh($cached)) {
            Log::info('Healthcare facilities cache hit.', ['cache_status' => 'fresh', 'facility_count' => count($cached['facilities'])]);

            return $this->result($cached['facilities'], true, false, 'cache', 'fresh', $facilityType, $search);
        }

        Log::info($cached === null ? 'Healthcare facilities cache miss.' : 'Healthcare facilities stale cache hit.');

        return $this->refreshWithLock($cacheKey, $latitude, $longitude, $radiusMetres, $cached, $facilityType, $search);
    }

    private function cacheKey(float $latitude, float $longitude, int $radiusMetres): string
    {
        return sprintf('healthcare_facilities:%s:%0.2f:%0.2f:%d', self::CACHE_VERSION, $latitude, $longitude, $radiusMetres);
    }

    /** @return array{facilities: array, fetched_at: int}|null */
    private function readCache(string $cacheKey): ?array
    {
        $cached = Cache::get($cacheKey);

        return is_array($cached) && is_array($cached['facilities'] ?? null) && is_int($cached['fetched_at'] ?? null) ? $cached : null;
    }

    private function isFresh(array $cached): bool
    {
        return $cached['fetched_at'] >= now()->subHours(self::FRESH_CACHE_HOURS)->getTimestamp();
    }

    private function refreshWithLock(string $cacheKey, float $latitude, float $longitude, int $radiusMetres, ?array $stale, ?string $facilityType, ?string $search): array
    {
        $lock = null;
        $acquired = false;
        try {
            $lock = Cache::lock("{$cacheKey}:refresh", self::LOCK_SECONDS);
            if ($stale !== null) {
                $acquired = $lock->get();
                if (! $acquired) {
                    Log::info('Healthcare facilities stale fallback used while refresh is in progress.', ['facility_count' => count($stale['facilities'])]);

                    return $this->result($stale['facilities'], true, true, 'stale_cache', 'stale', $facilityType, $search);
                }

                return $this->refresh($cacheKey, $latitude, $longitude, $radiusMetres, $stale, $facilityType, $search);
            }

            return $lock->block(self::LOCK_WAIT_SECONDS, function () use ($cacheKey, $latitude, $longitude, $radiusMetres, $facilityType, $search) {
                $cached = $this->readCache($cacheKey);
                if ($cached !== null) {
                    $fresh = $this->isFresh($cached);

                    return $this->result($cached['facilities'], true, ! $fresh, 'cache', $fresh ? 'fresh' : 'stale', $facilityType, $search);
                }

                return $this->refresh($cacheKey, $latitude, $longitude, $radiusMetres, null, $facilityType, $search);
            });
        } catch (LockTimeoutException) {
            $cached = $this->readCache($cacheKey);
            if ($cached !== null) {
                $fresh = $this->isFresh($cached);

                return $this->result($cached['facilities'], true, ! $fresh, 'cache', $fresh ? 'fresh' : 'stale', $facilityType, $search);
            }

            Log::warning('Healthcare facilities refresh lock timed out without a cached result.');

            return $this->result([], false, true, 'overpass', 'miss', $facilityType, $search);
        } catch (Throwable $e) {
            Log::warning('Healthcare facilities cache lock unavailable.', ['exception' => $e::class]);

            return $this->refresh($cacheKey, $latitude, $longitude, $radiusMetres, $stale, $facilityType, $search);
        } finally {
            if ($acquired && $lock instanceof Lock) {
                try {
                    $lock->release();
                } catch (Throwable) {
                    // Do not hide an otherwise valid response.
                }
            }
        }
    }

    private function refresh(string $cacheKey, float $latitude, float $longitude, int $radiusMetres, ?array $stale, ?string $facilityType, ?string $search): array
    {
        try {
            $facilities = $this->fetch($latitude, $longitude, $radiusMetres);
            Cache::put($cacheKey, ['facilities' => $facilities, 'fetched_at' => now()->getTimestamp()], now()->addDays(self::STALE_CACHE_DAYS));
            Log::info('Healthcare facilities refreshed from Overpass.', ['facility_count' => count($facilities)]);

            return $this->result($facilities, true, false, 'overpass', 'refreshed', $facilityType, $search);
        } catch (Throwable $e) {
            Log::warning('Unable to load healthcare facilities from Overpass.', ['exception' => $e::class, 'message' => $e->getMessage()]);
            if ($stale !== null) {
                Log::info('Healthcare facilities stale fallback used.', ['facility_count' => count($stale['facilities'])]);

                return $this->result($stale['facilities'], true, true, 'stale_cache', 'stale', $facilityType, $search);
            }

            return $this->result([], false, true, 'overpass', 'miss', $facilityType, $search);
        }
    }

    private function result(array $facilities, bool $available, bool $stale, string $source, string $cacheStatus, ?string $facilityType, ?string $search): array
    {
        return [
            'facilities' => $this->filter($facilities, $facilityType, $search),
            'available' => $available,
            'stale' => $stale,
            'source' => $source,
            'cache_status' => $cacheStatus,
        ];
    }

    private function filter(array $facilities, ?string $facilityType, ?string $search): array
    {
        return array_values(array_filter($facilities, function (array $facility) use ($facilityType, $search): bool {
            if ($facilityType && ($facility['facility_type'] ?? null) !== $facilityType) {
                return false;
            }

            return ! $search || $this->fuzzyContains(implode(' ', array_filter([
                $facility['facility_name'] ?? null,
                $facility['facility_name_en'] ?? null,
                $facility['address'] ?? null,
                $facility['province'] ?? null,
                $facility['district'] ?? null,
                $facility['sub_district'] ?? null,
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
            if ($leftPosition !== false && mb_strpos($text, $right, $leftPosition + mb_strlen($left)) !== false) {
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

        foreach (self::ENDPOINTS as $index => $endpoint) {
            Log::info('Requesting healthcare facilities from Overpass.', ['endpoint_index' => $index + 1]);
            try {
                $response = Http::asForm()->acceptJson()
                    ->withUserAgent('Checkup healthcare facility finder/1.0')
                    ->connectTimeout(self::OVERPASS_CONNECT_TIMEOUT_SECONDS)
                    ->timeout(self::OVERPASS_TIMEOUT_SECONDS)
                    ->post($endpoint, ['data' => $query]);
            } catch (Throwable $e) {
                Log::warning('Overpass request failed.', ['endpoint_index' => $index + 1, 'exception' => $e::class]);

                continue;
            }

            Log::info('Overpass response received.', ['endpoint_index' => $index + 1, 'status' => $response->status()]);
            if ($this->shouldTryNextEndpoint($response)) {
                continue;
            }

            $elements = $response->json('elements');
            if (! is_array($elements) || $elements === []) {
                Log::warning('Overpass returned an empty or invalid payload.', ['endpoint_index' => $index + 1]);

                continue;
            }

            $facilities = [];
            foreach ($elements as $element) {
                if (! is_array($element)) {
                    continue;
                }
                $facility = $this->normalise($element);
                if ($facility !== null) {
                    $facilities[$facility['facility_id']] = $facility;
                }
            }
            if ($facilities !== []) {
                return array_values($facilities);
            }
        }

        throw new \RuntimeException('Every configured Overpass endpoint failed or returned no usable facilities.');
    }

    private function shouldTryNextEndpoint(Response $response): bool
    {
        return ! $response->successful() || $response->status() === 429 || $response->serverError();
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
        $address = $tags['addr:full'] ?? implode(' ', array_filter([$tags['addr:housenumber'] ?? null, $tags['addr:street'] ?? null]));

        return $address !== '' ? $address : null;
    }
}
