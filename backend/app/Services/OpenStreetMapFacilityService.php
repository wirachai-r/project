<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenStreetMapFacilityService
{
    private const ENDPOINT = 'https://overpass-api.de/api/interpreter';

    public function nearby(float $latitude, float $longitude, int $radiusMetres = 10000, ?string $facilityType = null, ?string $search = null): array
    {
        $radiusMetres = max(1000, min($radiusMetres, 20000));
        $cacheKey = sprintf('osm-facilities:v2:%0.3f:%0.3f:%d', $latitude, $longitude, $radiusMetres);

        try {
            $facilities = Cache::store('file')->remember(
                $cacheKey,
                now()->addMinutes(30),
                fn () => $this->fetch($latitude, $longitude, $radiusMetres),
            );
        } catch (Throwable $e) {
            Log::warning('Unable to load nearby facilities from OpenStreetMap.', ['message' => $e->getMessage()]);
            return [];
        }

        return array_values(array_filter($facilities, function (array $facility) use ($facilityType, $search): bool {
            if ($facilityType && $facility['facility_type'] !== $facilityType) return false;
            return ! $search || mb_stripos($facility['facility_name'], $search) !== false;
        }));
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

        $response = Http::asForm()->acceptJson()
            ->withUserAgent('Checkup healthcare facility finder/1.0')
            ->connectTimeout(5)->timeout(25)->retry(2, 400)
            ->post(self::ENDPOINT, ['data' => $query])->throw();

        $facilities = [];
        foreach ($response->json('elements', []) as $element) {
            $facility = $this->normalise($element);
            if ($facility !== null) $facilities[$facility['facility_id']] = $facility;
        }
        return array_values($facilities);
    }

    private function normalise(array $element): ?array
    {
        $tags = $element['tags'] ?? [];
        $latitude = $element['lat'] ?? $element['center']['lat'] ?? null;
        $longitude = $element['lon'] ?? $element['center']['lon'] ?? null;
        $name = $tags['name:th'] ?? $tags['name'] ?? $tags['name:en'] ?? null;
        if ($latitude === null || $longitude === null || ! $name) return null;

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
