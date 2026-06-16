<?php

namespace App\Console\Commands;

use App\Services\Replenish\ReplenishService;
use Illuminate\Console\Command;

class ReplenishReport extends Command
{
    protected $signature = 'report:replenish
        {good : GOODSCODE товара}
        {--lead= : срок поставки в днях (заказ → приезд), обязательно}
        {--period=180 : окно анализа продаж в днях}
        {--json : вывести результат в JSON}';

    protected $description = 'Сколько закупить: спрос (опт+розница, сглаж.) × (срок поставки + интервал поставок) − свободно − в пути';

    public function handle(ReplenishService $service): int
    {
        $lead = $this->option('lead');
        if ($lead === null || !is_numeric($lead)) {
            $this->error('Укажите срок поставки: --lead=<дней>');
            return self::INVALID;
        }

        $report = $service->report(
            (int)$this->argument('good'),
            (int)$lead,
            (int)$this->option('period')
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
            'Товар %s%s — окно %s … %s (%d дн.)',
            $r['good']['GOODSCODE'],
            $r['good']['name'] ? ' (' . $r['good']['name'] . ')' : '',
            $r['window']['from'],
            $r['window']['to'],
            $r['window']['days']
        ));

        $unit = $r['good']['unit'] ?: 'шт';

        $this->table(['Показатель', 'Значение'], [
            ['Продажи опт', $r['sales']['opt'] . ' ' . $unit],
            ['Продажи розница', $r['sales']['retail'] . ' ' . $unit],
            ['Продано всего', $r['sales']['total'] . ' ' . $unit],
            ['Спрос/день (среднее)', $r['sales']['meanPerDay']],
            ['Спрос/день (сглаж., в расчёте)', $r['sales']['smoothedPerDay']],
            ['Интервал поставок, дн.', $r['supply']['intervalDays'] ?? 'нет истории → берём срок поставки'],
            ['Срок поставки, дн.', $r['supply']['leadDays']],
            ['Покрыть, дн.', $r['supply']['coverDays']],
            ['На складе', $r['stock']['warehouse'] . ' ' . $unit],
            ['В магазине', $r['stock']['retailStore'] . ' ' . $unit],
            ['В резерве', $r['stock']['reserved'] . ' ' . $unit],
            ['Свободно', $r['stock']['available'] . ' ' . $unit],
            ['В пути (заказано)', $r['stock']['inTransit'] . ' ' . $unit],
            ['Целевой запас', $r['target'] . ' ' . $unit],
        ]);

        $this->newLine();
        $this->line("<info>Рекомендуем заказать:</info> <comment>{$r['toOrder']} {$unit}</comment>");
    }
}
