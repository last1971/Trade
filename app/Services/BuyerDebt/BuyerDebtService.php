<?php

namespace App\Services\BuyerDebt;

use App\Buyer;
use App\Invoice;
use Illuminate\Support\Collection;

/**
 * Расчёт долгов покупателя по незакрытым счетам.
 *
 * Единственный источник правды для отчёта: и artisan-команда, и API-экспорт зовут report().
 * Ничего не пересчитывают сами.
 */
class BuyerDebtService
{
    /** Статусы счёта «в работе, не закрыт»: Зарезервирован / В подборке / Подобран. */
    private const STATUSES = [2, 3, 4];

    /**
     * Полный отчёт по покупателю.
     *
     * @param string $pokupatcode POKUPATCODE покупателя
     * @param string|null $from дата (YYYY-MM-DD), ранее которой счета не берём
     * @return array{buyer: array, invoices: array, totals: array}
     */
    public function report(string $pokupatcode, ?string $from = null): array
    {
        $invoices = Invoice::query()
            ->where('POKUPATCODE', $pokupatcode)
            ->whereIn('STATUS', self::STATUSES)
            ->when($from, fn($query) => $query->where('DATA', '>=', $from))
            ->with([
                'invoiceLines.transferOutLines',
                'invoiceLines.orderLinesTransit',
                'invoiceLines.pickUps',
                'invoiceLines.reserves',
                'invoiceLines.name',
                'transferOuts.transferOutLines',
                'cashFlows',
                'deposits',
            ])
            ->orderBy('DATA')
            ->get()
            ->map(fn(Invoice $invoice) => $this->buildInvoice($invoice))
            ->all();

        return [
            'buyer' => [
                'POKUPATCODE' => $pokupatcode,
                'name' => trim((string)optional(Buyer::find($pokupatcode))->SHORTNAME),
            ],
            'invoices' => $invoices,
            'totals' => $this->buildTotals($invoices),
        ];
    }

    /** Разбор одного счёта. */
    private function buildInvoice(Invoice $invoice): array
    {
        $sum = round($invoice->invoiceLines->sum('SUMMAP'), 2);
        $paidBank = round($invoice->cashFlows->sum('MONEYSCHET'), 2);
        // Зачёт депозита: SUMMA<0 — депозит зачтён в оплату счёта (+), SUMMA>0 — деньги ушли с счёта на депозит (−).
        $deposit = round(-$invoice->deposits->sum('SUMMA'), 2);
        $shipped = round(
            $invoice->transferOuts->sum(fn($upd) => $upd->transferOutLines->sum('SUMMAP')),
            2
        );

        $payments = $this->allocatePayments($invoice);
        $goods = $this->splitGoods($invoice);

        return [
            'SCODE' => $invoice->SCODE,
            'NS' => $invoice->NS,
            'DATA' => $invoice->DATA,
            'STATUS' => $invoice->STATUS,
            'sum' => $sum,
            'paidBank' => $paidBank,
            'deposit' => $deposit,
            'shipped' => $shipped,
            'debt' => round($sum - $paidBank - $deposit, 2),
            'remainingToShip' => round($sum - $shipped, 2),
            'onHand' => $goods['onHand'],
            'coming' => $goods['coming'],
            'notComing' => $goods['notComing'],
            'upds' => $payments['list'],
            'unallocatedPayment' => $payments['unallocated'],
            'holes' => $goods['holes'],
        ];
    }

    /**
     * Эвристика «какие УПД оплачены»: прямые платежи по SFCODE1, остаток — FIFO по дате УПД.
     * Депозит, зачтённый в счёт, попадает в общий пул.
     *
     * @return array{list: array, unallocated: float}
     */
    private function allocatePayments(Invoice $invoice): array
    {
        // Шаг 1: прямые платежи по каждому УПД (SFCODE1).
        $upds = $invoice->transferOuts->map(function ($upd) use ($invoice) {
            $summa = round($upd->transferOutLines->sum('SUMMAP'), 2);
            $direct = $invoice->cashFlows
                ->filter(fn($cf) => $cf->SFCODE1 && $cf->SFCODE1 == $upd->SFCODE)
                ->sum('MONEYSCHET');

            return [
                'SFCODE' => $upd->SFCODE,
                'NSF' => $upd->NSF,
                'DATA' => $upd->DATA,
                'summa' => $summa,
                'allocated' => min($direct, $summa),
                'overflow' => max(0, $direct - $summa),
            ];
        })->all();

        // Шаг 2: пул нераспределённых платежей (без SFCODE1) + зачёт депозита + излишки прямых платежей.
        $pool = $invoice->cashFlows->filter(fn($cf) => !$cf->SFCODE1)->sum('MONEYSCHET');
        $pool += -$invoice->deposits->sum('SUMMA');
        foreach ($upds as $upd) {
            $pool += $upd['overflow'];
        }

        // Шаг 3: FIFO по дате УПД (затем по SFCODE) — гасим остатки.
        usort($upds, fn($a, $b) => [$a['DATA'], $a['SFCODE']] <=> [$b['DATA'], $b['SFCODE']]);

        foreach ($upds as &$upd) {
            $remaining = $upd['summa'] - $upd['allocated'];
            if ($remaining > 0 && $pool > 0) {
                $cover = min($remaining, $pool);
                $upd['allocated'] += $cover;
                $pool -= $cover;
            }
        }
        unset($upd);

        $list = array_map(function ($upd) {
            $paid = round($upd['allocated'], 2);
            $remaining = round($upd['summa'] - $paid, 2);
            return [
                'SFCODE' => $upd['SFCODE'],
                'NSF' => $upd['NSF'],
                'DATA' => $upd['DATA'],
                'summa' => $upd['summa'],
                'paid' => $paid,
                'remaining' => $remaining,
                'status' => $remaining <= 0 ? 'оплачен' : ($paid > 0 ? 'частично' : 'не оплачен'),
            ];
        }, $upds);

        return [
            'list' => $list,
            'unallocated' => round(max(0, $pool), 2), // аванс/переплата, не разнесён по УПД
        ];
    }

    /**
     * Разбивка неотгруженного товара по источникам покрытия остатка:
     * «на складе» (подобрано/зарезервировано, ещё не отгружено) → «едет» (заказ поставщику, статусы 2,3) → «не едет» (дыра).
     *
     * @return array{onHand: float, coming: float, notComing: float, holes: array}
     */
    private function splitGoods(Invoice $invoice): array
    {
        $onHand = 0.0;
        $coming = 0.0;
        $notComing = 0.0;
        $holes = [];

        foreach ($invoice->invoiceLines as $line) {
            $shippedQty = $line->transferOutLines->sum('QUAN');
            $remainingQty = $line->QUAN - $shippedQty;
            if ($remainingQty <= 0) {
                continue;
            }

            // Делим остаток по количеству: на складе → едет → дыра.
            // 1) Подобрано/зарезервировано на складе и ещё не отгружено.
            $stockAvail = max(0, $this->stockQty($line->pickUps) + $this->stockQty($line->reserves) - $shippedQty);
            $stockQty = min($remainingQty, $stockAvail);
            // 2) Из непокрытого складом — что едет от поставщика (заказы статус 2,3).
            $stillNeed = $remainingQty - $stockQty;
            $transitQty = $line->orderLinesTransit->sum('QUAN');
            $comingQty = min($stillNeed, $transitQty);
            // 3) Остаток — реальная дыра.
            $holeQty = max(0, $stillNeed - $transitQty);

            // Деньги — от остаточного SUMMAP строки (как «Сумма»/«Отгружено»), а не QUAN×PRICE:
            // разносим пропорционально количеству, чтобы Склад+Едет+Не едет точно сошлось с «К отгрузке».
            $remainingMoney = round($line->SUMMAP - $line->transferOutLines->sum('SUMMAP'), 2);
            $money = $this->splitMoney($remainingMoney, $remainingQty, [
                'onHand' => $stockQty,
                'coming' => $comingQty,
                'notComing' => $holeQty,
            ]);

            $onHand += $money['onHand'];
            $coming += $money['coming'];
            $notComing += $money['notComing'];

            if ($holeQty > 0) {
                $holes[] = [
                    'SCODE' => $invoice->SCODE,
                    'NS' => $invoice->NS,
                    'GOODSCODE' => $line->GOODSCODE,
                    'name' => trim((string)optional($line->name)->NAME),
                    'qty' => round($holeQty, 3),
                    'price' => round($line->PRICE, 2),
                    'sum' => $money['notComing'],
                ];
            }
        }

        return [
            'onHand' => round($onHand, 2),
            'coming' => round($coming, 2),
            'notComing' => round($notComing, 2),
            'holes' => $holes,
        ];
    }

    /** Количество на складе по строке-источнику (PODBPOS/RESERVEDPOS): склад + магазин. */
    private function stockQty(Collection $rows): float
    {
        return $rows->sum('QUANSKLAD') + $rows->sum('QUANSHOP');
    }

    /**
     * Разнести денежную сумму $money по корзинам пропорционально их количеству ($total — общее кол-во).
     * Все корзины округляются до копеек, последняя ненулевая забирает остаток — сумма корзин точно равна $money.
     *
     * @param array<string,float> $qtys количество в каждой корзине
     * @return array<string,float> деньги по каждой корзине
     */
    private function splitMoney(float $money, float $total, array $qtys): array
    {
        $result = array_fill_keys(array_keys($qtys), 0.0);

        $lastKey = null;
        foreach ($qtys as $key => $qty) {
            if ($qty > 0) {
                $lastKey = $key;
            }
        }
        if ($lastKey === null) {
            return $result;
        }

        $allocated = 0.0;
        foreach ($qtys as $key => $qty) {
            if ($qty <= 0) {
                continue;
            }
            if ($key === $lastKey) {
                $result[$key] = round($money - $allocated, 2);
            } else {
                $result[$key] = round($money * $qty / $total, 2);
                $allocated += $result[$key];
            }
        }

        return $result;
    }

    /** Итоги по всем счетам. */
    private function buildTotals(array $invoices): array
    {
        $rows = new Collection($invoices);
        $sum = fn(string $key) => round($rows->sum($key), 2);

        $debt = $sum('debt');
        $notComing = $sum('notComing');

        return [
            'sum' => $sum('sum'),
            'paidBank' => $sum('paidBank'),
            'deposit' => $sum('deposit'),
            'shipped' => $sum('shipped'),
            'debt' => $debt,
            'remainingToShip' => $sum('remainingToShip'),
            'onHand' => $sum('onHand'),
            'coming' => $sum('coming'),
            'notComing' => $notComing,
            'oweConcrete' => round($debt - $notComing, 2),
        ];
    }
}
