<?php

namespace App\Services\BuyerDebt;

/** Распределение ожидаемых поставок по дате счёта, SCODE и строке. */
class TransitAllocator
{
    /**
     * Строки всех открытых счетов для товаров, по которым есть поставка.
     * onHand учитывается тем же способом, что в BuyerDebtService::splitGoods.
     *
     * @param array<array{line: int, invoice: int, good: int, date: string,
     *     qty: float, shipped: float, picked: float, reserved: float}> $demand
     * @param array<int, float> $supply неполученный остаток по GOODSCODE
     * @return array<int, float> количество для каждой REALPRICECODE
     */
    public function allocate(array $demand, array $supply): array
    {
        usort($demand, fn($a, $b) =>
            [(string)$a['date'], (int)$a['invoice'], (int)$a['line']]
            <=> [(string)$b['date'], (int)$b['invoice'], (int)$b['line']]
        );

        $result = [];
        foreach ($demand as $line) {
            $good = (int)$line['good'];
            $remaining = max(0.0, (float)$line['qty'] - (float)$line['shipped']);
            $stock = max(0.0, (float)$line['picked'] + (float)$line['reserved'] - (float)$line['shipped']);
            $need = max(0.0, $remaining - $stock);
            $coming = min($need, max(0.0, (float)($supply[$good] ?? 0.0)));
            $result[(int)$line['line']] = $coming;
            $supply[$good] = max(0.0, ($supply[$good] ?? 0.0) - $coming);
        }

        return $result;
    }
}
