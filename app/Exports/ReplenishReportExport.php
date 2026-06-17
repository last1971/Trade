<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel детального отчёта по одному товару (показатель → значение).
 * Данные берёт из готового ReplenishService::report() — ничего не считает.
 */
class ReplenishReportExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private array $report)
    {
    }

    public function title(): string
    {
        return 'Закупка';
    }

    public function headings(): array
    {
        $r = $this->report;

        return [
            'Товар',
            ($r['good']['name'] ?: $r['good']['GOODSCODE'])
            . ' — окно ' . $r['window']['from'] . ' … ' . $r['window']['to'] . ' (' . $r['window']['days'] . ' дн.)',
        ];
    }

    public function array(): array
    {
        $r = $this->report;
        $unit = $r['good']['unit'] ?: 'шт';

        return [
            ['Код', $r['good']['GOODSCODE']],
            ['Корпус', $r['good']['body']],
            ['Производитель', $r['good']['producer']],
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
            ['Рекомендуем заказать', $r['toOrder'] . ' ' . $unit],
        ];
    }
}
