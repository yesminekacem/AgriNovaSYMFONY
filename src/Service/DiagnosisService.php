<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DiagnosisService
{
    private HttpClientInterface $http;
    private ?string $apiUrl;

    public function __construct(HttpClientInterface $http)
    {
        $this->http = $http;
        $this->apiUrl = $_ENV['DIAGNOSIS_API_URL'] ?? null;
    }

    /**
     * Send the image to an inference endpoint if configured.
     * Fallback returns a safe default.
     */
    public function diagnose(string $filePath): array
    {
        if ($this->apiUrl) {
            try {
                $response = $this->http->request('POST', $this->apiUrl, [
                    'body' => [
                        'image' => fopen($filePath, 'r'),
                    ],
                ]);

                $status = $response->getStatusCode();
                $content = $response->getContent(false);
                $data = json_decode($content, true) ?: [];
                if ($status >= 200 && $status < 300 && isset($data['disease']) && isset($data['confidence'])) {
                    return $data;
                }

                // return error info for debugging
                return [
                    'disease' => 'unknown',
                    'confidence' => 0.0,
                    'error' => sprintf('Inference endpoint returned status %d: %s', $status, substr($content, 0, 1000)),
                ];
            } catch (\Throwable $e) {
                return [
                    'disease' => 'unknown',
                    'confidence' => 0.0,
                    'error' => 'Request failed: ' . $e->getMessage(),
                ];
            }
        }

        return [
            'disease' => 'unknown',
            'confidence' => 0.0,
        ];
    }
}
