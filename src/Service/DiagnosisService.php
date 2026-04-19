<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DiagnosisService
{
    private HttpClientInterface $http;
    private ?string $apiUrl;

    public function __construct(HttpClientInterface $http, string $apiUrl = '')
    {
        $this->http   = $http;
        $this->apiUrl = $apiUrl ?: null;
    }

    public function diagnose(string $filePath): array
    {
        if (!$this->apiUrl) {
            return ['disease' => 'unknown', 'confidence' => 0.0];
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'disease'    => 'unknown',
                'confidence' => 0.0,
                'error'      => 'Image file not found or not readable',
            ];
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [
                'disease'    => 'unknown',
                'confidence' => 0.0,
                'error'      => 'Failed to open image file',
            ];
        }

        try {
            $response = $this->http->request('POST', $this->apiUrl, [
                'body' => ['image' => $handle],
            ]);

            $status  = $response->getStatusCode();
            $content = $response->getContent(false);
            $data    = json_decode($content, true) ?: [];

            if ($status >= 200 && $status < 300 && isset($data['disease'], $data['confidence'])) {
                return [
                    'disease'    => $data['disease'],
                    'confidence' => (float) $data['confidence'],
                ];
            }

            return [
                'disease'    => 'unknown',
                'confidence' => 0.0,
                'error'      => sprintf(
                    'Inference endpoint returned status %d: %s',
                    $status,
                    substr($content, 0, 200) // trimmed for safety
                ),
            ];

        } catch (\Throwable $e) {
            return [
                'disease'    => 'unknown',
                'confidence' => 0.0,
                'error'      => 'Request failed: ' . $e->getMessage(),
            ];
        } finally {
            fclose($handle);
        }
    }
}