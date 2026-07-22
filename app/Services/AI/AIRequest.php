<?php

declare(strict_types=1);

namespace App\Services\AI;

final class AIRequest
{
    /**
     * @param string $staticPrompt Статичная часть промпта (инструкции, кэшируется у Claude)
     * @param string $dynamicPrompt Динамическая часть (данные)
     * @param float $temperature Температура генерации (0.0 - 1.0). Игнорируется на моделях,
     *                           где параметр удалён (Sonnet 5 / Opus 4.8 / Fable 5)
     * @param int $maxTokens Максимум токенов в ответе
     * @param string|null $thinking Управление «мышлением» Claude: 'disabled' (экономия токенов
     *                              на справочных задачах), 'adaptive' или null (дефолт модели).
     *                              У остальных провайдеров игнорируется
     */
    public function __construct(
        public string $staticPrompt,
        public string $dynamicPrompt,
        public float $temperature = 0.7,
        public int $maxTokens = 4096,
        public ?string $thinking = null,
    ) {
    }

    /**
     * Получить полный промпт (для провайдеров без кэширования)
     */
    public function getFullPrompt(): string
    {
        return $this->staticPrompt . "\n\n" . $this->dynamicPrompt;
    }
}
