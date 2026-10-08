<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel страницы «Разгребание склада». Строки — из готового StockClassifService::list(),
 * названия категорий — из его же categories(). Ничего не считает.
 */
class StockClassifExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    /**
     * @param array $rows строки list(..., all: true)['data']
     * @param array $categories categories(): [{code, name, subcategories: [{id, name}]}]
     */
    public function __construct(private array $rows, private array $categories)
    {
    }

    public function title(): string
    {
        return 'Разгребание склада';
    }

    public function headings(): array
    {
        return [
            'Код', 'Наименование', 'Категория', 'Подкатегория', 'Остаток', 'Стоимость', 'Непокрыто',
            'Маркировка', 'ТНВЭД', 'ОКПД2', 'Сертификат', 'МП', 'Коды ЧЗ',
        ];
    }

    public function array(): array
    {
        $catNames = [];
        $subNames = [];
        foreach ($this->categories as $category) {
            $catNames[$category['code']] = $category['name'];
            foreach ($category['subcategories'] ?? [] as $sub) {
                $subNames[$sub['id']] = $sub['name'];
            }
        }

        return array_map(function ($row) use ($catNames, $subNames) {
            // Вердикт и коды — с основной строки классификации (IS_PRIMARY идёт первой)
            $classifs = collect($row['classifs']);
            $primary = $classifs->first();
            $marking = $classifs->isEmpty()
                ? 'не проверяли'
                : ($classifs->contains(fn($c) => $c->MARK_REQUIRED) ? 'подлежит' : 'не подлежит');

            return [
                $row['GOODSCODE'],
                $row['NAME'],
                $catNames[$row['category']] ?? '',
                $subNames[$row['subcategory']] ?? '',
                $row['OST'],
                $row['VAL'],
                $row['UNCOVERED'],
                $marking,
                $primary->TNVED ?? '',
                $primary->OKPD2 ?? '',
                $row['problem_no_cert'] ? 'нет' : 'есть',
                implode(', ', array_map(fn($m) => ['ozon' => 'Озон', 'wb' => 'ВБ'][$m] ?? $m, $row['mp'])),
                $row['CODES'],
            ];
        }, $this->rows);
    }
}
