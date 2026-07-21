<?php

declare(strict_types=1);

namespace App\Services\AI;

interface AIProviderInterface
{
    /**
     * Генерация контента
     */
    public function generate(AIRequest $request, string $model): AIResponse;

    /**
     * Название провайдера (claude, openai, yandex)
     */
    public function getName(): string;

    /**
     * Человекочитаемое название провайдера
     */
    public function getDisplayName(): string;

    /**
     * Список доступных моделей
     *
     * @return array<string, array{
     *     name: string,
     *     input_cost: float,
     *     output_cost: float,
     *     max_tokens: int,
     *     tier: string,
     *     supports_cache: bool,
     *     supports_web_search: bool
     * }>
     */
    public function getModels(): array;

    /**
     * Проверить доступность провайдера (есть ли API ключ)
     */
    public function isAvailable(): bool;

    /**
     * Рассчитать стоимость запроса в USD
     */
    public function calculateCost(string $model, int $inputTokens, int $outputTokens): float;
}
