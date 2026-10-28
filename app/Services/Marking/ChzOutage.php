<?php

namespace App\Services\Marking;

/**
 * Отказ пачки от сбоя Честного знака (или связи с ним), а не по существу документа:
 * такой пачке поможет повтор, человека звать незачем. Единственное место, где решается,
 * что повторять автоматически и когда — очередь только спрашивает.
 *
 * Сбоем считается то, что до документа не дошло: сервис ЧЗ не ответил, ГИС МТ ответила
 * 5xx, «upstream» шлюза, DNS. Отказ по существу («отбил отчёт», 400), «пустой ответ»
 * (документ мог создаться), «ответ не получен» и отказ из-за предшественника не повторяем:
 * предшественника повторяют, и ждущий его ввод в оборот оживает сам.
 * Тексты — живые отказы прода 09.09–21.10.2026 (тест ChzOutageTest).
 */
final class ChzOutage
{
    /** Сколько раз повторяем одну пачку: дальше ЧЗ лежит всерьёз — зовём человека. */
    public const MAX_ATTEMPTS = 5;

    /** Пауза перед повтором растёт с номером попытки: 15, 30, 45… минут от создания пачки. */
    public const STEP_MINUTES = 15;

    /** Метка автоповтора в CREATED_BY: по ней считаем попытки. */
    private const BY = 'auto:повтор ';

    private const PATTERNS = [
        '/^chz-сервис недоступен:/u',
        '/^chz-сервис: [^:]+: 5\d\d\b/u',
        "/Unrecognized token 'upstream'/u",
        '/upstream connect error|no healthy upstream/u',
        '/EAI_AGAIN|ECONNRESET|ECONNREFUSED|ETIMEDOUT/u',
    ];

    public static function is(?string $error): bool
    {
        $error = (string)$error;
        // Только собственный ответ сервиса ЧЗ: пометки поверх него («Предшественник отбит: …»,
        // «Повторена пачкой …») повтор не включают, даже если внутри тот же сбой.
        if (mb_strpos($error, 'chz-сервис') !== 0) {
            return false;
        }
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $error)) {
                return true;
            }
        }
        return false;
    }

    /** Какая это попытка: 0 — пачка из первоначальной сборки или повтор человеком. */
    public static function attempt(?string $createdBy): int
    {
        $createdBy = (string)$createdBy;
        return mb_strpos($createdBy, self::BY) === 0 ? (int)mb_substr($createdBy, mb_strlen(self::BY)) : 0;
    }

    /** CREATED_BY пачки-повтора. */
    public static function by(int $attempt): string
    {
        return self::BY . $attempt;
    }

    /** Повторим ли сами: сбой и попытки не кончились. Иначе отказ разбирает человек. */
    public static function willRetry(?string $error, ?string $createdBy): bool
    {
        return self::is($error) && self::attempt($createdBy) < self::MAX_ATTEMPTS;
    }

    /** Пора ли повторять пачку, отбитую сбоем, спустя $ageMinutes от её создания. */
    public static function due(?string $error, ?string $createdBy, int $ageMinutes): bool
    {
        return self::willRetry($error, $createdBy)
            && $ageMinutes >= self::STEP_MINUTES * (self::attempt($createdBy) + 1);
    }
}
