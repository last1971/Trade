<?php

declare(strict_types=1);

namespace App\Services\AI;

final class AIResponse
{
    /**
     * @param string $content Текст ответа
     * @param int $inputTokens Количество входных токенов
     * @param int $outputTokens Количество выходных токенов
     * @param string $model Использованная модель
     * @param string $provider Провайдер (claude, openai, yandex)
     * @param float $cost Расчётная стоимость в USD
     * @param bool $cached Был ли использован кэш (Claude)
     */
    public function __construct(
        public string $content,
        public int $inputTokens,
        public int $outputTokens,
        public string $model,
        public string $provider,
        public float $cost,
        public bool $cached = false,
    ) {
    }

    /**
     * Парсинг JSON из ответа
     *
     * @return array<string, mixed>
     */
    public function parseJson(): array
    {
        $text = $this->content;

        // Извлекаем JSON из markdown блока если есть
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
            $text = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $text, $matches)) {
            $text = $matches[1];
        }

        $text = trim($text);

        // Убираем BOM если есть
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;

        // Нормализуем переносы строк
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // JSON не поддерживает literal newlines внутри строк - экранируем их
        // Находим все строковые значения и экранируем там переносы
        $text = preg_replace_callback(
            '/"((?:[^"\\\\]|\\\\.)*)"/u',
            function ($matches) {
                $str = $matches[1];
                // Экранируем literal newlines и tabs внутри строк
                $str = str_replace(["\n", "\t"], ['\\n', '\\t'], $str);
                // Удаляем остальные control characters
                $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str) ?? $str;
                return '"' . $str . '"';
            },
            $text
        ) ?? $text;

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($text, true, 512, \JSON_INVALID_UTF8_IGNORE);

        if ($decoded === null && json_last_error() !== \JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse JSON response: ' . json_last_error_msg() . "\nResponse: " . $text);
        }

        return $decoded ?? [];
    }

    /**
     * Общее количество токенов
     */
    public function getTotalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }
}
