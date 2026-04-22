<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AgriAiCopilotService
{
    private const OPENAI_RESPONSES_ENDPOINT = 'https://api.openai.com/v1/responses';
    private const GROQ_CHAT_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function isConfigured(): bool
    {
        return trim($this->getApiKeyForProvider($this->getProvider())) !== '';
    }

    public function getProvider(): string
    {
        $configuredProvider = strtolower(trim((string) ($_ENV['AI_PROVIDER'] ?? $_SERVER['AI_PROVIDER'] ?? '')));
        if (in_array($configuredProvider, ['openai', 'groq'], true)) {
            return $configuredProvider;
        }

        if (trim($this->getGroqApiKey()) !== '') {
            return 'groq';
        }

        return 'openai';
    }

    public function getProviderLabel(): string
    {
        return $this->getProvider() === 'groq' ? 'Groq' : 'OpenAI';
    }

    public function getModel(): string
    {
        return $this->getProvider() === 'groq'
            ? $this->getGroqModel()
            : $this->getOpenAiModel();
    }

    public function generateDashboardBrief(array $dashboardSnapshot): array
    {
        if (!$this->isConfigured()) {
            return [
                'status' => 'not_configured',
                'error' => sprintf(
                    'Add your %s key in .env.local to enable the live AI brief.',
                    $this->getProvider() === 'groq' ? 'GROQ_API_KEY' : 'OPENAI_API_KEY'
                ),
                'provider' => $this->getProviderLabel(),
                'model' => $this->getModel(),
                'generatedAt' => null,
                'brief' => null,
            ];
        }

        $result = $this->getProvider() === 'groq'
            ? $this->requestGroqBrief($dashboardSnapshot)
            : $this->requestOpenAiBrief($dashboardSnapshot);

        if (($result['status'] ?? null) !== 'success') {
            $result['provider'] = $this->getProviderLabel();
            $result['model'] = $this->getModel();
            $result['generatedAt'] = null;
            $result['brief'] = null;

            return $result;
        }

        return [
            'status' => 'success',
            'error' => null,
            'provider' => $this->getProviderLabel(),
            'model' => $this->getModel(),
            'generatedAt' => new \DateTimeImmutable(),
            'brief' => $result['brief'],
        ];
    }

    private function requestOpenAiBrief(array $dashboardSnapshot): array
    {
        try {
            $payload = $this->httpClient->request('POST', self::OPENAI_RESPONSES_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->getOpenAiApiKey(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->getOpenAiModel(),
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => $this->buildSystemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->buildUserPrompt($dashboardSnapshot),
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'agrinova_dashboard_brief',
                            'strict' => true,
                            'schema' => $this->getBriefSchema(),
                        ],
                    ],
                    'max_output_tokens' => 700,
                ],
                'timeout' => 25,
                'no_proxy' => '*',
            ])->toArray(false);
        } catch (ExceptionInterface|\Throwable) {
            return [
                'status' => 'error',
                'error' => 'The AI brief could not be generated right now. Please try again in a moment.',
            ];
        }

        if (isset($payload['error']['message'])) {
            return [
                'status' => 'error',
                'error' => (string) $payload['error']['message'],
            ];
        }

        return $this->buildSuccessfulResult($this->extractOpenAiOutputText($payload));
    }

    private function requestGroqBrief(array $dashboardSnapshot): array
    {
        try {
            $payload = $this->httpClient->request('POST', self::GROQ_CHAT_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->getGroqApiKey(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->getGroqModel(),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->buildSystemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->buildUserPrompt($dashboardSnapshot),
                        ],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'agrinova_dashboard_brief',
                            'strict' => true,
                            'schema' => $this->getBriefSchema(),
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_completion_tokens' => 700,
                ],
                'timeout' => 25,
                'no_proxy' => '*',
            ])->toArray(false);
        } catch (ExceptionInterface|\Throwable) {
            return [
                'status' => 'error',
                'error' => 'The AI brief could not be generated right now. Please try again in a moment.',
            ];
        }

        if (isset($payload['error']['message'])) {
            return [
                'status' => 'error',
                'error' => (string) $payload['error']['message'],
            ];
        }

        $apiErrorMessage = (string) ($payload['error']['message'] ?? '');
        if ($apiErrorMessage !== '' && $this->shouldRetryGroqAsJsonObject($apiErrorMessage)) {
            return $this->requestGroqJsonObjectBrief($dashboardSnapshot);
        }

        return $this->buildSuccessfulResult($this->extractGroqOutputText($payload));
    }

    private function requestGroqJsonObjectBrief(array $dashboardSnapshot): array
    {
        try {
            $payload = $this->httpClient->request('POST', self::GROQ_CHAT_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->getGroqApiKey(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->getGroqModel(),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->buildSystemPrompt().' Return only a valid JSON object. Do not add markdown, comments, or extra text.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->buildGroqJsonObjectPrompt($dashboardSnapshot),
                        ],
                    ],
                    'response_format' => [
                        'type' => 'json_object',
                    ],
                    'temperature' => 0.2,
                    'max_completion_tokens' => 700,
                ],
                'timeout' => 25,
                'no_proxy' => '*',
            ])->toArray(false);
        } catch (ExceptionInterface|\Throwable) {
            return [
                'status' => 'error',
                'error' => 'The AI brief could not be generated right now. Please try again in a moment.',
            ];
        }

        if (isset($payload['error']['message'])) {
            return [
                'status' => 'error',
                'error' => (string) $payload['error']['message'],
            ];
        }

        return $this->buildSuccessfulResult($this->extractGroqOutputText($payload));
    }

    private function buildSuccessfulResult(?string $outputText): array
    {
        if ($outputText === null) {
            return [
                'status' => 'error',
                'error' => 'The AI response was empty or could not be parsed.',
            ];
        }

        try {
            /** @var array<string, mixed> $brief */
            $brief = json_decode($outputText, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [
                'status' => 'error',
                'error' => 'The AI response format was invalid.',
            ];
        }

        $normalizedBrief = $this->normalizeBrief($brief);
        if ($normalizedBrief === null) {
            return [
                'status' => 'error',
                'error' => 'The AI brief was missing required insight sections.',
            ];
        }

        return [
            'status' => 'success',
            'brief' => $normalizedBrief,
        ];
    }

    private function buildSystemPrompt(): string
    {
        return 'You are AgriNova AI Copilot, an agricultural operations advisor. Analyze only the provided dashboard data. Return concise management insights for inventory, rentals, and risk exposure. Do not invent missing facts.';
    }

    private function buildUserPrompt(array $dashboardSnapshot): string
    {
        return "Build an executive JSON brief for this dashboard snapshot.\nFocus on inventory efficiency, rental bottlenecks, risk, and growth opportunities.\nKeep list items short and concrete.\n\nDashboard snapshot:\n".json_encode($dashboardSnapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function buildGroqJsonObjectPrompt(array $dashboardSnapshot): string
    {
        return <<<PROMPT
Return a single valid JSON object with exactly these keys:
- headline: string
- summary: string
- priority: one of "Low", "Moderate", "High", "Critical"
- confidence: integer from 0 to 100
- risks: array of 1 to 3 short strings
- actions: array of 1 to 3 short strings
- opportunities: array of 1 to 3 short strings

Rules:
- No markdown
- No explanation outside JSON
- No extra keys
- Use empty-safe practical values if data is limited

Dashboard snapshot:
PROMPT
        ."\n".json_encode($dashboardSnapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function getBriefSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['headline', 'summary', 'priority', 'confidence', 'risks', 'actions', 'opportunities'],
            'properties' => [
                'headline' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'priority' => [
                    'type' => 'string',
                    'enum' => ['Low', 'Moderate', 'High', 'Critical'],
                ],
                'confidence' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                ],
                'risks' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'actions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'opportunities' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }

    private function getOpenAiApiKey(): string
    {
        return trim((string) ($_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? ''));
    }

    private function getGroqApiKey(): string
    {
        return trim((string) ($_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? ''));
    }

    private function getApiKeyForProvider(string $provider): string
    {
        return $provider === 'groq' ? $this->getGroqApiKey() : $this->getOpenAiApiKey();
    }

    private function getOpenAiModel(): string
    {
        $model = trim((string) ($_ENV['OPENAI_MODEL'] ?? $_SERVER['OPENAI_MODEL'] ?? ''));

        return $model !== '' ? $model : 'gpt-4.1-mini';
    }

    private function getGroqModel(): string
    {
        $model = trim((string) ($_ENV['GROQ_MODEL'] ?? $_SERVER['GROQ_MODEL'] ?? ''));

        return $model !== '' ? $model : 'openai/gpt-oss-20b';
    }

    private function extractOpenAiOutputText(array $payload): ?string
    {
        foreach ($payload['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    return (string) $content['text'];
                }

                if (($content['type'] ?? null) === 'refusal' && isset($content['refusal'])) {
                    return null;
                }
            }
        }

        return null;
    }

    private function extractGroqOutputText(array $payload): ?string
    {
        $content = $payload['choices'][0]['message']['content'] ?? null;

        return is_string($content) && trim($content) !== '' ? $content : null;
    }

    private function normalizeBrief(array $brief): ?array
    {
        $headline = trim((string) ($brief['headline'] ?? ''));
        $summary = trim((string) ($brief['summary'] ?? ''));
        $priority = trim((string) ($brief['priority'] ?? ''));
        $confidence = (int) ($brief['confidence'] ?? 0);
        $risks = $this->normalizeList($brief['risks'] ?? []);
        $actions = $this->normalizeList($brief['actions'] ?? []);
        $opportunities = $this->normalizeList($brief['opportunities'] ?? []);

        if ($headline === '' || $summary === '' || $priority === '') {
            return null;
        }

        if ($risks === [] || $actions === [] || $opportunities === []) {
            return null;
        }

        return [
            'headline' => $headline,
            'summary' => $summary,
            'priority' => $priority,
            'confidence' => max(0, min(100, $confidence)),
            'risks' => array_slice($risks, 0, 3),
            'actions' => array_slice($actions, 0, 3),
            'opportunities' => array_slice($opportunities, 0, 3),
        ];
    }

    private function normalizeList(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            $text = trim((string) $item);
            if ($text !== '') {
                $normalized[] = $text;
            }
        }

        return $normalized;
    }

    private function shouldRetryGroqAsJsonObject(string $message): bool
    {
        $message = strtolower($message);

        return str_contains($message, 'failed to validate json')
            || str_contains($message, 'failed_generation')
            || str_contains($message, 'generated json does not match the expected schema');
    }
}
