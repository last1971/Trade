<?php

namespace App\Services\BuyerDebt;

use App\Buyer;
use App\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /** Человекочитаемые названия статусов счёта по STATUS (единый источник для Excel и страницы). */
    public const STATUS_LABELS = [
        0 => 'Формируется',
        1 => 'Сформирован',
        2 => 'Резерв',
        3 => 'Подборка',
        4 => 'Подобран',
        5 => 'Закрыт',
        6 => 'Корзина',
    ];

    /**
     * Полный отчёт по покупателю.
     *
     * @param string $pokupatcode POKUPATCODE покупателя
     * @param string|null $from дата (YYYY-MM-DD), ранее которой счета не берём
     * @return array{buyer: array, invoices: array, totals: array}
     */
    public function report(string $pokupatcode, ?string $from = null): array
    {
        $invoices = $this->loadInvoices($pokupatcode, $from)->all();

        return [
            'buyer' => [
                'POKUPATCODE' => $pokupatcode,
                'name' => trim((string)optional(Buyer::find($pokupatcode))->SHORTNAME),
            ],
            'invoices' => $invoices,
            'totals' => $this->buildTotals($invoices),
        ];
    }

    /**
     * Сводный отчёт по всем покупателям за период.
     *
     * Долговые колонки (debt/oweConcrete) считаются ровно тем же кодом, что и report() —
     * одним батч-запросом всех открытых счетов за период (без N+1), затем группировка по покупателю.
     * Движенческие колонки (счета/отгрузки/поступления за период) — лёгкими GROUP BY к Firebird.
     * Строки — объединение покупателей из всех источников (отгрузка без счёта и денег тоже попадёт).
     *
     * @param string|null $from дата (YYYY-MM-DD) начала периода
     * @param string|null $to дата (YYYY-MM-DD) конца периода
     * @return array<int, array>
     */
    public function summary(?string $from = null, ?string $to = null): array
    {
        // Движение за период (всё, кроме Корзины) — дешёвые GROUP BY.
        $movement = $this->movementSummary($from, $to);

        // Долг по открытым счетам — тоже GROUP BY. Это чистые суммы (строки − оплаты − депозит),
        // поэтому совпадает с totals.debt страницы «Долги». Верхнюю границу периода НЕ применяем
        // (как «Долги»: фильтр только по from), чтобы кнопка «Открыть долги» давала те же цифры.
        $debt = $this->debtSummary($from);

        // Объединение ключей из всех источников (пустые коды отбрасываем — это документы без покупателя).
        $codes = collect(array_keys($debt))
            ->merge(array_keys($movement['invoices']))
            ->merge(array_keys($movement['shipped']))
            ->merge(array_keys($movement['paid']))
            ->filter(fn($code) => $code !== null && $code !== '')
            ->unique()
            ->values();

        $names = Buyer::whereIn('POKUPATCODE', $codes->all())->pluck('SHORTNAME', 'POKUPATCODE');

        return $codes
            ->map(fn($code) => [
                'POKUPATCODE' => $code,
                'name' => trim((string)($names[$code] ?? '')),
                'invoices' => $movement['invoices'][$code] ?? 0.0,
                'shipped' => $movement['shipped'][$code] ?? 0.0,
                'paid' => $movement['paid'][$code] ?? 0.0,
                'updCount' => $movement['updCount'][$code] ?? 0,
                'debt' => $debt[$code] ?? 0.0,
            ])
            ->filter(fn($row) => $row['invoices'] != 0 || $row['shipped'] != 0
                || $row['paid'] != 0 || $row['debt'] != 0)
            ->values()
            ->all();
    }

    /**
     * Долг по открытым счетам на покупателя: Σстрок − Σоплат + Σдепозитов (та же формула, что
     * totals.debt в report(), только агрегатами без гидрации). Все три суммы привязаны к открытым
     * счетам (STATUS 2,3,4) с DATA >= from — иначе цифра не сойдётся с вкладкой «Долги».
     *
     * @return array<int,float> POKUPATCODE => долг
     */
    private function debtSummary(?string $from): array
    {
        $conn = DB::connection('firebird');
        $open = fn($table, $col) => $conn->table($table)
            ->join('S', 'S.SCODE', '=', "$table.SCODE")
            ->whereIn('S.STATUS', self::STATUSES)
            ->when($from, fn($q) => $q->where('S.DATA', '>=', $from))
            ->groupBy('S.POKUPATCODE')
            ->get(['S.POKUPATCODE as p', DB::raw("sum($col) as \"s\"")]);

        $debt = [];
        foreach ($open('REALPRICE', 'REALPRICE.SUMMAP') as $row) {
            $debt[$row->p] = round((float)$row->s, 2);                       // Σ строк счетов
        }
        foreach ($open('SCHET', 'SCHET.MONEYSCHET') as $row) {
            $debt[$row->p] = round(($debt[$row->p] ?? 0) - (float)$row->s, 2); // − Σ оплат
        }
        foreach ($open('DEPOSIT', 'DEPOSIT.SUMMA') as $row) {
            $debt[$row->p] = round(($debt[$row->p] ?? 0) + (float)$row->s, 2); // + Σ депозитов (deposit = −SUMMA в report)
        }

        return $debt;
    }

    /**
     * Загрузка открытых счетов с полным набором связей и сборкой через buildInvoice().
     * Единственное место, где живёт список eager-load связей — и для одиночного отчёта, и для сводки.
     * Eager-load батчит связи по всем счетам сразу, поэтому N+1 нет даже без фильтра по покупателю.
     *
     * @return Collection<int, array> массив разобранных счетов (buildInvoice)
     */
    private function loadInvoices(?string $pokupatcode, ?string $from, ?string $to = null): Collection
    {
        return Invoice::query()
            ->when($pokupatcode, fn($query) => $query->where('POKUPATCODE', $pokupatcode))
            ->whereIn('STATUS', self::STATUSES)
            ->when($from, fn($query) => $query->where('DATA', '>=', $from))
            ->when($to, fn($query) => $query->where('DATA', '<=', $to))
            ->with([
                'invoiceLines.transferOutLines',
                'invoiceLines.orderLinesTransit',
                'invoiceLines.pickUps',
                'invoiceLines.reserves',
                'invoiceLines.name',
                'invoiceLines.good',
                'transferOuts.transferOutLines',
                'cashFlows',
                'deposits',
            ])
            ->orderBy('DATA')
            ->get()
            ->map(fn(Invoice $invoice) => $this->buildInvoice($invoice));
    }

    /**
     * Суммы движения по покупателям за период (раздельно: счета / отгрузки / поступления).
     * Плоские GROUP BY к Firebird, raw-алиасы в кавычках (иначе Firebird их аплертит).
     *
     * @return array{invoices: array<int,float>, shipped: array<int,float>, paid: array<int,float>, updCount: array<int,int>}
     */
    private function movementSummary(?string $from, ?string $to): array
    {
        $conn = DB::connection('firebird');

        // Счета (S ⨝ REALPRICE), все статусы кроме Корзины (6).
        $invoices = [];
        $rows = $conn->table('S')
            ->join('REALPRICE', 'REALPRICE.SCODE', '=', 'S.SCODE')
            ->where('S.STATUS', '<>', 6)
            ->when($from, fn($q) => $q->where('S.DATA', '>=', $from))
            ->when($to, fn($q) => $q->where('S.DATA', '<=', $to))
            ->groupBy('S.POKUPATCODE')
            ->get(['S.POKUPATCODE as p', DB::raw('sum(REALPRICE.SUMMAP) as "s"')]);
        foreach ($rows as $row) {
            $invoices[$row->p] = round((float)$row->s, 2);
        }

        // Отгрузки/УПД (SF ⨝ REALPRICEF): сумма + количество УПД.
        $shipped = [];
        $updCount = [];
        $rows = $conn->table('SF')
            ->join('REALPRICEF', 'REALPRICEF.SFCODE', '=', 'SF.SFCODE')
            ->when($from, fn($q) => $q->where('SF.DATA', '>=', $from))
            ->when($to, fn($q) => $q->where('SF.DATA', '<=', $to))
            ->groupBy('SF.POKUPATCODE')
            ->get([
                'SF.POKUPATCODE as p',
                DB::raw('sum(REALPRICEF.SUMMAP) as "s"'),
                DB::raw('count(distinct SF.SFCODE) as "c"'),
            ]);
        foreach ($rows as $row) {
            $shipped[$row->p] = round((float)$row->s, 2);
            $updCount[$row->p] = (int)$row->c;
        }

        // Поступления денег (SCHET). Покупателя берём через счёт (SCODE → S.POKUPATCODE):
        // SCHET.POKUPATCODE ненадёжен (у части платежей пуст/служебный), а привязка к счёту —
        // тот же принцип, что paidBank в report() и в долговой колонке.
        $paid = [];
        $rows = $conn->table('SCHET')
            ->join('S', 'S.SCODE', '=', 'SCHET.SCODE')
            ->where('S.STATUS', '<>', 6)
            ->when($from, fn($q) => $q->where('SCHET.DATA', '>=', $from))
            ->when($to, fn($q) => $q->where('SCHET.DATA', '<=', $to))
            ->groupBy('S.POKUPATCODE')
            ->get(['S.POKUPATCODE as p', DB::raw('sum(SCHET.MONEYSCHET) as "s"')]);
        foreach ($rows as $row) {
            $paid[$row->p] = round((float)$row->s, 2);
        }

        return ['invoices' => $invoices, 'shipped' => $shipped, 'paid' => $paid, 'updCount' => $updCount];
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
            'statusLabel' => self::STATUS_LABELS[$invoice->STATUS] ?? $invoice->STATUS,
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
                    'body' => trim((string)optional($line->good)->BODY),
                    'producer' => trim((string)optional($line->good)->PRODUCER),
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
