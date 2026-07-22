<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

use App\Services\AI\AIProviderInterface;
use App\Services\AI\AIRequest;
use App\Services\AI\AIResponse;
use Illuminate\Support\Facades\Http;

final class ClaudeProvider implements AIProviderInterface
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    // Цены — USD за 1M токенов (справочник claude-api, актуально на 2026-07).
    // ID — алиасы (без датовых суффиксов), валидны для /v1/messages.
    private const MODELS = [
        'claude-haiku-4-5' => [
            'name' => 'Claude Haiku 4.5',
            'input_cost' => 1.00,
            'output_cost' => 5.00,
            'max_tokens' => 64000,
            'tier' => 'cheap',
            'supports_cache' => true,
            'supports_web_search' => true,
            'supports_sampling' => true,      // temperature ещё принимается
            'thinking_configurable' => true,  // мышление по умолчанию выключено
        ],
        'claude-sonnet-5' => [
            'name' => 'Claude Sonnet 5',
            'input_cost' => 3.00,   // вводная цена $2 действует до 2026-08-31
            'output_cost' => 15.00, // вводная цена $10 действует до 2026-08-31
            'max_tokens' => 128000,
            'tier' => 'medium',
            'supports_cache' => true,
            'supports_web_search' => true,
            'supports_sampling' => false,     // temperature удалён — 400
            'thinking_configurable' => true,  // adaptive по умолчанию ВКЛ — выключать явно
        ],
        'claude-opus-4-8' => [
            'name' => 'Claude Opus 4.8',
            'input_cost' => 5.00,
            'output_cost' => 25.00,
            'max_tokens' => 128000,
            'tier' => 'expensive',
            'supports_cache' => true,
            'supports_web_search' => true,
            'supports_sampling' => false,
            'thinking_configurable' => true,  // по умолчанию мышление выключено
        ],
        'claude-fable-5' => [
            'name' => 'Claude Fable 5',
            'input_cost' => 10.00,
            'output_cost' => 50.00,
            'max_tokens' => 128000,
            'tier' => 'expensive',
            'supports_cache' => true,
            'supports_web_search' => true,
            'supports_sampling' => false,
            'thinking_configurable' => false, // мышление всегда ВКЛ, disabled = 400
        ],
    ];

    public function __construct(
        private string $apiKey = '',
        private ?string $proxyUrl = null,
    ) {
    }

    public function generate(AIRequest $request, string $model): AIResponse
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Claude API key is not configured');
        }

        if (!isset(self::MODELS[$model])) {
            throw new \InvalidArgumentException(sprintf('Unknown model: %s', $model));
        }

        $modelConfig = self::MODELS[$model];

        $headers = [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => self::API_VERSION,
            'anthropic-beta' => 'prompt-caching-2024-07-31',
        ];

        // Кэшируем статичную часть промпта
        $content = [
            [
                'type' => 'text',
                'text' => $request->staticPrompt,
                'cache_control' => ['type' => 'ephemeral'],
            ],
            [
                'type' => 'text',
                'text' => $request->dynamicPrompt,
            ],
        ];

        $jsonBody = [
            'model' => $model,
            'max_tokens' => min($request->maxTokens, $modelConfig['max_tokens']),
            'messages' => [
                ['role' => 'user', 'content' => $content],
            ],
        ];

        // temperature удалён на Sonnet 5 / Opus 4.8 / Fable 5 (400) — шлём только там, где принимается
        if ($request->temperature > 0 && ($modelConfig['supports_sampling'] ?? false)) {
            $jsonBody['temperature'] = $request->temperature;
        }

        // thinking: 'disabled' экономит токены на справочных задачах (у Sonnet 5 adaptive ВКЛ по
        // умолчанию). У Fable 5 мышление всегда включено — disabled даст 400, поэтому не трогаем.
        if ($request->thinking !== null && ($modelConfig['thinking_configurable'] ?? true)) {
            $jsonBody['thinking'] = ['type' => $request->thinking];
        }

        $response = Http::withHeaders($headers)
            ->when($this->proxyUrl, fn ($client) => $client->withOptions(['proxy' => $this->proxyUrl]))
            ->timeout(120)
            ->retry(3, 2000, throw: false)
            ->post(self::API_URL, $jsonBody);

        if ($response->failed()) {
            throw new \RuntimeException(sprintf(
                'Claude error (%d): %s',
                $response->status(),
                $this->extractErrorMessage($response->body())
            ));
        }

        /** @var array{
         *     content: array<int, array{type: string, text?: string}>,
         *     usage: array{input_tokens: int, output_tokens: int, cache_creation_input_tokens?: int, cache_read_input_tokens?: int}
         * } $data
         */
        $data = $response->json();

        $text = $this->extractText($data);
        $inputTokens = $data['usage']['input_tokens'];
        $outputTokens = $data['usage']['output_tokens'];
        $cacheWrite = $data['usage']['cache_creation_input_tokens'] ?? 0;
        $cacheRead = $data['usage']['cache_read_input_tokens'] ?? 0;
        $cached = $cacheRead > 0;

        // Стоимость с учётом кэша: запись кэша ×1.25, чтение кэша ×0.10 от input_cost
        $in = $modelConfig['input_cost'];
        $cost = (
            $inputTokens * $in
            + $cacheWrite * $in * 1.25
            + $cacheRead * $in * 0.10
            + $outputTokens * $modelConfig['output_cost']
        ) / 1_000_000;

        return new AIResponse(
            content: $text,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            model: $model,
            provider: $this->getName(),
            cost: $cost,
            cached: $cached,
        );
    }

    public function getName(): string
    {
        return 'claude';
    }

    public function getDisplayName(): string
    {
        return 'Claude (Anthropic)';
    }

    public function getModels(): array
    {
        return self::MODELS;
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    public function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        $config = self::MODELS[$model] ?? self::MODELS[array_key_first(self::MODELS)];

        return ($inputTokens * $config['input_cost'] + $outputTokens * $config['output_cost']) / 1_000_000;
    }

    /**
     * @param array{content: array<int, array{type: string, text?: string}>} $response
     */
    private function extractText(array $response): string
    {
        foreach ($response['content'] as $block) {
            if ($block['type'] === 'text' && isset($block['text'])) {
                return $block['text'];
            }
        }

        return '';
    }

    private function extractErrorMessage(string $body): string
    {
        /** @var array{error?: array{message?: string}}|null $data */
        $data = json_decode($body, true);

        return $data['error']['message'] ?? $body;
    }
}
