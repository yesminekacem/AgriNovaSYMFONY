<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AgriWeatherService
{
    private const GEOCODING_ENDPOINT = 'https://geocoding-api.open-meteo.com/v1/search';
    private const FORECAST_ENDPOINT = 'https://api.open-meteo.com/v1/forecast';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function getMaintenanceWeather(string $city): ?array
    {
        $location = $this->geocode($city);
        if ($location === null) {
            return null;
        }

        $forecast = $this->forecast($location['latitude'], $location['longitude'], 3);
        if ($forecast === null) {
            return null;
        }

        return [
            'location' => $location,
            'current' => $forecast['current'],
            'days' => $forecast['days'],
        ];
    }

    public function getRentalWeatherBrief(string $locationHint, ?\DateTimeInterface $startDate, ?\DateTimeInterface $endDate): ?array
    {
        $location = $this->geocode($locationHint);
        if ($location === null) {
            return null;
        }

        $forecast = $this->forecast($location['latitude'], $location['longitude'], 7);
        if ($forecast === null) {
            return null;
        }

        return [
            'location' => $location,
            'current' => $forecast['current'],
            'days' => $forecast['days'],
            'startWindow' => $this->findForecastForDate($forecast['days'], $startDate),
            'endWindow' => $this->findForecastForDate($forecast['days'], $endDate),
        ];
    }

    private function geocode(string $query): ?array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::GEOCODING_ENDPOINT, [
                'query' => [
                    'name' => $query,
                    'count' => 1,
                    'language' => 'en',
                    'format' => 'json',
                ],
                'no_proxy' => '*',
                'timeout' => 5,
            ])->toArray();
        } catch (ExceptionInterface|\Throwable) {
            return null;
        }

        $result = $response['results'][0] ?? null;
        if (!is_array($result)) {
            return null;
        }

        return [
            'name' => $result['name'] ?? $query,
            'country' => $result['country'] ?? null,
            'admin1' => $result['admin1'] ?? null,
            'timezone' => $result['timezone'] ?? 'auto',
            'latitude' => (float) ($result['latitude'] ?? 0),
            'longitude' => (float) ($result['longitude'] ?? 0),
        ];
    }

    private function forecast(float $latitude, float $longitude, int $days): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::FORECAST_ENDPOINT, [
                'query' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'timezone' => 'auto',
                    'forecast_days' => max(1, min($days, 7)),
                    'current' => 'temperature_2m,precipitation,wind_speed_10m',
                    'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,wind_speed_10m_max',
                ],
                'no_proxy' => '*',
                'timeout' => 5,
            ])->toArray();
        } catch (ExceptionInterface|\Throwable) {
            return null;
        }

        $daily = $response['daily'] ?? [];
        $times = $daily['time'] ?? [];
        if (!is_array($times)) {
            return null;
        }

        $daysData = [];
        foreach ($times as $index => $date) {
            $rainProbability = $daily['precipitation_probability_max'][$index] ?? null;
            $windMax = $daily['wind_speed_10m_max'][$index] ?? null;

            $daysData[] = [
                'date' => $date,
                'tempMax' => $daily['temperature_2m_max'][$index] ?? null,
                'tempMin' => $daily['temperature_2m_min'][$index] ?? null,
                'rainMm' => $daily['precipitation_sum'][$index] ?? null,
                'rainProbability' => $rainProbability,
                'windMax' => $windMax,
                'risk' => $this->classifyRisk($rainProbability, $windMax),
            ];
        }

        return [
            'current' => [
                'temperature' => $response['current']['temperature_2m'] ?? null,
                'precipitation' => $response['current']['precipitation'] ?? null,
                'wind' => $response['current']['wind_speed_10m'] ?? null,
                'time' => $response['current']['time'] ?? null,
            ],
            'days' => $daysData,
        ];
    }

    private function findForecastForDate(array $days, ?\DateTimeInterface $date): ?array
    {
        if ($date === null) {
            return null;
        }

        $target = $date->format('Y-m-d');
        foreach ($days as $day) {
            if (($day['date'] ?? null) === $target) {
                return $day;
            }
        }

        return null;
    }

    private function classifyRisk(mixed $rainProbability, mixed $windMax): string
    {
        $rainProbability = is_numeric($rainProbability) ? (float) $rainProbability : 0.0;
        $windMax = is_numeric($windMax) ? (float) $windMax : 0.0;

        if ($rainProbability >= 60 || $windMax >= 35) {
            return 'High';
        }

        if ($rainProbability >= 30 || $windMax >= 20) {
            return 'Moderate';
        }

        return 'Low';
    }
}
