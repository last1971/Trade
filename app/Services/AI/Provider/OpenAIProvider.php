<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

use App\Services\AI\AIProviderInterface;
use App\Services\AI\AIRequest;
use App\Services\AI\AIResponse;
use Illuminate\Support\Facades\Http;

final class OpenAIProvider implements AIProviderInterface
{
    private const API_URL = 'https://api.openai.com/v1/responses';

    private const MODELS = [
        'gpt-5-nano' => [
            'name' => 'GPT-5 Nano',
            'input_cost' => 0.05,
            'output_cost' => 0.40,
            'max_tokens' => 128000,
            'tier' => 'cheap',
            'supports_cache' => true,
            'supports_web_search' => false,
        ],
        'gpt-4o-mini' => [
            'name' => 'GPT-4o Mini',
            'input_cost' => 0.15,
            'output_cost' => 0.60,
            'max_tokens' => 16384,
            'tier' => 'cheap',
            'supports_cache' => false,
            'supports_web_search' => true,
        ],
        'gpt-5-mini' => [
            'name' => 'GPT-5 Mini',
            'input_cost' => 0.25,
            'output_cost' => 2.00,
            'max_tokens' => 128000,
            'tier' => 'cheap',
            'supports_cache' => true,
            'supports_web_search' => true,
        ],
        'gpt-5' => [
            'name' => 'GPT-5',
            'input_cost' => 1.25,
            'output_cost' => 10.00,
            'max_tokens' => 128000,
            'tier' => 'medium',
            'supports_cache' => true,
            'supports_web_search' => true,
        ],
        'gpt-4o' => [
            'name' => 'GPT-4o',
            'input_cost' => 2.50,
            'output_cost' => 10.00,
            'max_tokens' => 16384,
            'tier' => 'medium',
            'supports_cache' => false,
            'supports_web_search' => true,
        ],
        'o1' => [
            'name' => 'o1 (Reasoning)',
            'input_cost' => 15.00,
            'output_cost' => 60.00,
            'max_tokens' => 100000,
            'tier' => 'expensive',
            'supports_cache' => false,
            'supports_web_search' => false,
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
            throw new \RuntimeException('OpenAI API key is not configured');
        }

        if (!isset(self::MODELS[$model])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown OpenAI model: %s. Available: %s',
                $model,
                implode(', ', array_keys(self::MODELS)),
            ));
        }

        $modelConfig = self::MODELS[$model];

        // Формируем input с ролями
        $input = [];

        // developer role (бывший system)
        if ($request->staticPrompt !== '') {
            $input[] = [
                'role' => 'developer',
                'content' => $request->staticPrompt,
            ];
        }

        // user role
        $input[] = [
            'role' => 'user',
            'content' => $request->dynamicPrompt,
        ];

        $jsonBody = [
            'model' => $model,
            'input' => $input,
            'max_output_tokens' => min($request->maxTokens, $modelConfig['max_tokens']),
        ];

        // Reasoning модели (o1, gpt-5*) не поддерживают temperature
        $isReasoningModel = $model === 'o1' || str_starts_with($model, 'gpt-5');
        if ($isReasoningModel) {
            $jsonBody['reasoning'] = ['effort' => 'medium'];
        } elseif ($request->temperature > 0) {
            $jsonBody['temperature'] = $request->temperature;
        }

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
            ->when($this->proxyUrl, fn ($client) => $client->withOptions(['proxy' => $this->proxyUrl]))
            ->timeout(120)
            ->retry(3, 2000, throw: false)
            ->post(self::API_URL, $jsonBody);

        if ($response->failed()) {
            $body = $response->body();

            throw match ($response->status()) {
                401 => new \RuntimeException('OpenAI: Invalid API key'),
                429 => new \RuntimeException('OpenAI: Rate limit exceeded. Try again later.'),
                400 => new \RuntimeException('OpenAI: Bad request. ' . $this->extractErrorMessage($body)),
                404 => new \RuntimeException('OpenAI: Model not found or not available for your account.'),
                default => new \RuntimeException(sprintf('OpenAI error (%d): %s', $response->status(), $this->extractErrorMessage($body))),
            };
        }

        /** @var array{
         *     output: array<int, array{type: string, content?: array<int, array{type: string, text?: string}>}>,
         *     usage: array{input_tokens: int, output_tokens: int}
         * } $data
         */
        $data = $response->json();

        $text = $this->extractText($data);
        $inputTokens = $data['usage']['input_tokens'];
        $outputTokens = $data['usage']['output_tokens'];

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
        return 'openai';
    }

    public function getDisplayName(): string
    {
        return 'OpenAI';
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
     * @param array{output: array<int, array{type: string, content?: array<int, array{type: string, text?: string}>}>} $response
     */
    private function extractText(array $response): string
    {
        foreach ($response['output'] as $block) {
            if ($block['type'] === 'message' && isset($block['content'])) {
                foreach ($block['content'] as $content) {
                    if ($content['type'] === 'output_text' && isset($content['text'])) {
                        return $content['text'];
                    }
                }
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
