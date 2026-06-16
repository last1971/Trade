<?php

namespace App\Console\Commands;

use App\Services\Replenish\ReplenishService;
use Illuminate\Console\Command;

class ReplenishListReport extends Command
{
    protected $signature = 'report:replenish-list
        {--lead= : срок поставки в днях (один на весь прогон), обязательно}
        {--period1=30 : окно скрининга продаж в днях}
        {--period2=180 : окно расчёта спроса в днях}
        {--min-sales=2 : оставлять позиции, где продаж за период1 больше этого числа}
        {--json : вывести результат в JSON}';

    protected $description = 'Список «что закупить» по всему каталогу: продажи за период1 → остаток < продаж → детальный расчёт за период2 → заказать > 0';

    public function handle(ReplenishService $service): int
    {
        $lead = $this->option('lead');
        if ($lead === null || !is_numeric($lead)) {
            $this->error('Укажите срок поставки: --lead=<дней>');
            return self::INVALID;
        }

        $report = $service->list(
            (int)$lead,
            (int)$this->option('period1'),
            (int)$this->option('period2'),
            (int)$this->option('min-sales')
        );

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->render($report);

        return self::SUCCESS;
    }

    /** Человекочитаемый вывод (только отображение, расчёт — в сервисе). */
    private function render(array $r): void
    {
        $this->info(sprintf(
            'Скрининг %s … %s (%d дн.) | срок поставки %d дн., окно спроса %d дн., порог продаж > %d',
            $r['window']['from'],
            $r['window']['to'],
            $r['window']['days'],
            $r['params']['leadDays'],
            $r['params']['period2'],
            $r['params']['minSales']
        ));
        $this->line(sprintf(
            'Прошли по числу продаж: %d → остаток < продаж: %d → к заказу: %d',
            $r['screened'],
            $r['candidates'],
            count($r['rows'])
        ));
        $this->newLine();

        if (!$r['rows']) {
            $this->warn('Нет позиций к заказу.');
            return;
        }

        $this->table(
            ['Код', 'Название', 'Производитель', 'Продано', 'Заказать'],
            array_map(fn($row) => [
                $row['GOODSCODE'],
                $row['name'],
                $row['producer'],
                $row['soldPeriod1'],
                $row['toOrder'],
            ], $r['rows'])
        );
    }
}
