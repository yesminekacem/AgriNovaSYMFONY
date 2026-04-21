<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AgriLocationService
{
    private const SEARCH_ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function searchLocation(string $query): ?array
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::SEARCH_ENDPOINT, [
                'headers' => [
                    'User-Agent' => 'AgriNovaSYMFONY/1.0 student-project',
                ],
                'query' => [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'addressdetails' => 1,
                ],
                'no_proxy' => '*',
                'timeout' => 6,
            ])->toArray();
        } catch (ExceptionInterface|\Throwable) {
            return null;
        }

        $location = $response[0] ?? null;
        if (!is_array($location)) {
            return null;
        }

        $lat = (float) ($location['lat'] ?? 0);
        $lon = (float) ($location['lon'] ?? 0);

        return [
            'displayName' => $location['display_name'] ?? $query,
            'latitude' => $lat,
            'longitude' => $lon,
            'osmType' => $location['osm_type'] ?? null,
            'category' => $location['category'] ?? null,
            'type' => $location['type'] ?? null,
            'mapEmbedUrl' => $this->buildEmbedUrl($lat, $lon),
            'mapOpenUrl' => sprintf('https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=13/%s/%s', $lat, $lon, $lat, $lon),
        ];
    }

    private function buildEmbedUrl(float $latitude, float $longitude): string
    {
        $offset = 0.03;
        $left = $longitude - $offset;
        $right = $longitude + $offset;
        $top = $latitude + $offset;
        $bottom = $latitude - $offset;

        return sprintf(
            'https://www.openstreetmap.org/export/embed.html?bbox=%s,%s,%s,%s&layer=mapnik&marker=%s,%s',
            $left,
            $bottom,
            $right,
            $top,
            $latitude,
            $longitude
        );
    }
}
