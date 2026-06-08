<?php

namespace App\Console\Commands;

use App\Services\BuyerDebt\BuyerDebtService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\TableSeparator;

class InvoiceDebtReport extends Command
{
    protected $signature = 'report:invoice-debt {buyer : POKUPATCODE покупателя} {--from= : дата (YYYY-MM-DD), ранее которой счета не берём} {--json : Вывести результат в JSON}';

    protected $description = 'Долги покупателя: незакрытые счета (статус 2,3,4), оплата по УПД (SFCODE1 + FIFO), товар едет/не едет';

    public function handle(BuyerDebtService $service): int
    {
        $report = $service->report($this->argument('buyer'), $this->option('from') ?: null);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->renderTables($report);

        return self::SUCCESS;
    }

    /** Человекочитаемый вывод в консоль (только отображение, расчёт — в сервисе). */
    private function renderTables(array $report): void
    {
        $invoices = $report['invoices'];
        $totals = $report['totals'];

        $this->info(sprintf(
            'Покупатель %s%s — счетов в работе: %d',
            $report['buyer']['POKUPATCODE'],
            $report['buyer']['name'] ? ' (' . $report['buyer']['name'] . ')' : '',
            count($invoices)
        ));

        $rows = array_map(fn($inv) => [
            $inv['NS'],
            $inv['DATA'],
            $inv['STATUS'],
            $inv['sum'],
            $inv['paidBank'],
            $inv['deposit'],
            $inv['shipped'],
            $inv['debt'],
            $inv['remainingToShip'],
            $inv['onHand'],
            $inv['coming'],
            $inv['notComing'],
        ], $invoices);

        $rows[] = new TableSeparator();
        $rows[] = [
            'ИТОГО', '', '',
            $totals['sum'],
            $totals['paidBank'],
            $totals['deposit'],
            $totals['shipped'],
            $totals['debt'],
            $totals['remainingToShip'],
            $totals['onHand'],
            $totals['coming'],
            $totals['notComing'],
        ];

        $this->table(
            ['Счёт', 'Дата', 'Ст', 'Сумма', 'Оплачено', 'Депозит', 'Отгружено', 'Долг', 'К отгрузке', 'На складе', 'Едет', 'Не едет'],
            $rows
        );

        $this->newLine();
        $this->line("<info>Должны денег</info> (Сумма − Оплачено − Депозит): <comment>{$totals['debt']}</comment>");
        $this->line("<info>Конкретно должны</info> (Должны − Не едет): <comment>{$totals['oweConcrete']}</comment>");

        foreach ($invoices as $inv) {
            $this->newLine();
            $this->line("<comment>Счёт №{$inv['NS']} (SCODE {$inv['SCODE']})</comment>");

            if ($inv['upds']) {
                $this->table(
                    ['УПД №', 'Дата', 'Сумма', 'Оплачено', 'Остаток', 'Статус'],
                    array_map(fn($u) => [
                        $u['NSF'], $u['DATA'], $u['summa'], $u['paid'], $u['remaining'], $u['status'],
                    ], $inv['upds'])
                );
            } else {
                $this->line('  УПД нет');
            }

            if ($inv['unallocatedPayment'] > 0) {
                $this->warn("  Аванс/переплата не разнесён по УПД: {$inv['unallocatedPayment']}");
            }

            if ($inv['holes']) {
                $this->line("  <error>Не едет (нечем закрыть):</error> сумма {$inv['notComing']}");
                $this->table(
                    ['GOODSCODE', 'Товар', 'Кол-во', 'Цена', 'Сумма'],
                    array_map(fn($h) => [
                        $h['GOODSCODE'], $h['name'], $h['qty'], $h['price'], $h['sum'],
                    ], $inv['holes'])
                );
            }
        }
    }
}
