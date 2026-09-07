<?php

namespace App\Services\Marking;

use Psr\Http\Message\ResponseInterface;

/**
 * Выпуск КМ по GTIN через chz-сервис. Пул кодов живёт в сервисе; в MARKCODES они попадут
 * только при нанесении/вводе в оборот (отдельный этап). ИНН — только наш, из конфига.
 */
class ChzOrderService
{
    /** Разделитель групп GS1 внутри полного КМ, как отдаёт СУЗ. */
    public const GS = "\u{1D}";

    public function __construct(private ChzClient $client)
    {
    }

    /** Заказы, в которых есть GTIN, свежие сверху. */
    public function ordersByGtin(string $gtin): array
    {
        return $this->client->get("order/by-gtin/{$gtin}")['orders'] ?? [];
    }

    /**
     * Заказ N кодов на GTIN от нашей организации. requestId — идемпотентность на стороне
     * сервиса: повтор с тем же id вернёт тот же заказ, эмиссия второй раз не оплатится.
     */
    public function order(string $gtin, int $quantity, ?string $requestId): array
    {
        $inn = trim((string)config('marking.chz.inn'));
        if ($inn === '') {
            throw new MarkingException('Не задан ИНН организации для заказа КМ (MARKING_CHZ_INN)');
        }
        $headers = $requestId ? ['X-Request-Id' => $requestId] : [];
        return $this->client->post('order', [
            'inn' => $inn,
            'products' => [['gtin' => $gtin, 'quantity' => $quantity]],
        ], $headers);
    }

    /** Статус буферов СУЗ; готовые коды сервис забирает в свою базу сам. */
    public function status(string $orderId): array
    {
        return $this->client->get("order/{$orderId}/status");
    }

    /** Список PDF-пачек этикеток (без отрисовки). */
    public function pdfList(string $orderId, int $chunk): array
    {
        return $this->client->get("order/{$orderId}/pdf", ['chunk' => $chunk]);
    }

    /** PDF одной пачки. */
    public function pdfChunk(string $orderId, int $n, int $chunk): ResponseInterface
    {
        return $this->client->raw("order/{$orderId}/pdf/{$n}", ['chunk' => $chunk]);
    }

    /** Коды заказа (ki + полный km). */
    public function codes(string $orderId): array
    {
        return $this->client->get("order/{$orderId}/codes")['codes'] ?? [];
    }

    /**
     * CSV как выгрузка из ЛК СУЗ: один столбец без заголовка, полный КМ в строке,
     * GS — символ 0x1D как есть (файл для машин и нанесения, не для Excel).
     */
    public static function codesCsv(array $codes): string
    {
        $lines = array_map(fn($code) => $code['km'] ?? '', $codes);
        return implode("\r\n", array_filter($lines, fn($km) => $km !== '')) . "\r\n";
    }
}
