<?php

declare(strict_types=1);

namespace App\Services\AI;

final class AIRequest
{
    /**
     * @param string $staticPrompt Статичная часть промпта (инструкции, кэшируется у Claude)
     * @param string $dynamicPrompt Динамическая часть (данные)
     * @param float $temperature Температура генерации (0.0 - 1.0)
     * @param int $maxTokens Максимум токенов в ответе
     */
    public function __construct(
        public string $staticPrompt,
        public string $dynamicPrompt,
        public float $temperature = 0.7,
        public int $maxTokens = 4096,
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
