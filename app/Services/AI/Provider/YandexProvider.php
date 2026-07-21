<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

use App\Services\AI\AIProviderInterface;
use App\Services\AI\AIRequest;
use App\Services\AI\AIResponse;
use Illuminate\Support\Facades\Http;

final class YandexProvider implements AIProviderInterface
{
    private const API_URL = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion';

    private const MODELS = [
        'yandexgpt-lite' => [
            'name' => 'YandexGPT Lite',
            'input_cost_rub' => 0.20,  // ₽ за 1K токенов
            'output_cost_rub' => 0.20,
            'input_cost' => 2.20,      // $ за 1M токенов (примерно)
            'output_cost' => 2.20,
            'max_tokens' => 8192,
            'tier' => 'cheap',
            'supports_cache' => false,
            'supports_web_search' => false,
        ],
        'yandexgpt' => [
            'name' => 'YandexGPT Pro',
            'input_cost_rub' => 0.40,
            'output_cost_rub' => 0.40,
            'input_cost' => 4.40,
            'output_cost' => 4.40,
            'max_tokens' => 32000,
            'tier' => 'medium',
            'supports_cache' => false,
            'supports_web_search' => false,
        ],
    ];

    public function __construct(
        private string $apiKey = '',
        private string $folderId = '',
    ) {
    }

    public function generate(AIRequest $request, string $model): AIResponse
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Yandex API key or folder ID is not configured');
        }

        if (!isset(self::MODELS[$model])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown Yandex model: %s. Available: %s',
                $model,
                implode(', ', array_keys(self::MODELS))
            ));
        }

        $modelConfig = self::MODELS[$model];

        // Формируем messages
        $messages = [];

        if ($request->staticPrompt !== '') {
            $messages[] = [
                'role' => 'system',
                'text' => $request->staticPrompt,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'text' => $request->dynamicPrompt,
        ];

        $jsonBody = [
            'modelUri' => sprintf('gpt://%s/%s/latest', $this->folderId, $model),
            'completionOptions' => [
                'stream' => false,
                'temperature' => $request->temperature,
                'maxTokens' => (string) min($request->maxTokens, $modelConfig['max_tokens']),
            ],
            'messages' => $messages,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Api-Key ' . $this->apiKey,
            'x-folder-id' => $this->folderId,
            'x-data-logging-enabled' => 'false',
        ])
            ->timeout(120)
            ->retry(3, 2000, throw: false)
            ->post(self::API_URL, $jsonBody);

        if ($response->failed()) {
            $body = $response->body();

            throw match ($response->status()) {
                401, 403 => new \RuntimeException('Yandex: Invalid API key or folder ID'),
                429 => new \RuntimeException('Yandex: Rate limit exceeded. Try again later.'),
                400 => new \RuntimeException('Yandex: Bad request. ' . $this->extractErrorMessage($body)),
                default => new \RuntimeException(sprintf('Yandex error (%d): %s', $response->status(), $this->extractErrorMessage($body))),
            };
        }

        /** @var array{
         *     result: array{
         *         alternatives: array<int, array{message: array{text: string}}>,
         *         usage: array{inputTextTokens: string, completionTokens: string}
         *     }
         * } $data
         */
        $data = $response->json();

        $text = $data['result']['alternatives'][0]['message']['text'] ?? '';
        $inputTokens = (int) $data['result']['usage']['inputTextTokens'];
        $outputTokens = (int) $data['result']['usage']['completionTokens'];

        $cost = $this->calculateCost($model, $inputTokens, $outputTokens);

        return new AIResponse(
            content: $text,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            model: $model,
            provider: $this->getName(),
            cost: $cost,
            cached: false,
        );
    }

    public function getName(): string
    {
        return 'yandex';
    }

    public function getDisplayName(): string
    {
        return 'YandexGPT';
    }

    public function getModels(): array
    {
        return self::MODELS;
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '' && $this->folderId !== '';
    }

    public function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        $config = self::MODELS[$model] ?? self::MODELS[array_key_first(self::MODELS)];

        return ($inputTokens * $config['input_cost'] + $outputTokens * $config['output_cost']) / 1_000_000;
    }

    private function extractErrorMessage(string $body): string
    {
        /** @var array{message?: string, error?: array{message?: string}}|null $data */
        $data = json_decode($body, true);

        return $data['message'] ?? $data['error']['message'] ?? $body;
    }
}
