<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqModerationService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $groqApiKey,
        private string $groqModel,
    ) {
    }

    public function moderate(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [
                'safe' => true,
                'raw' => null,
            ];
        }

        $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->groqApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->groqModel,
                'temperature' => 0,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a content moderation classifier for a forum. '
                            . 'Return ONLY valid JSON with this exact shape: '
                            . '{"safe": true/false, "reason": "short reason"}. '
                            . 'Mark unsafe for insults, harassment, hate, threats, or explicit abusive language. '
                            . 'Mark safe for normal disagreement and neutral discussion.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ],
                'response_format' => [
                    'type' => 'json_object',
                ],
            ],
        ]);

        $data = $response->toArray(false);

        $content = $data['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            return [
                'safe' => true,
                'raw' => $data,
            ];
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded) || !array_key_exists('safe', $decoded)) {
            return [
                'safe' => true,
                'raw' => $data,
            ];
        }

        return [
            'safe' => (bool) $decoded['safe'],
            'reason' => (string) ($decoded['reason'] ?? ''),
            'raw' => $data,
        ];
    }
}