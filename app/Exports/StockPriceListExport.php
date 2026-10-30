<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Прайс остатков «Разгребания склада» в формате чужого шаблона (пример прайса.xlsx, 21.10.2026):
 * две строки шапки как в шаблоне, дальше по строке на товар. Ничего не считает —
 * строки готовит StockClassifService::priceList().
 */
class StockPriceListExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    /**
     * @param array $rows priceList(): [{code, title, description, dc, brand, available, price}]
     */
    public function __construct(private array $rows)
    {
    }

    public function title(): string
    {
        return 'Sheet1';
    }

    public function headings(): array
    {
        return [
            ['code', 'ID', 'title', 'description', 'dc', 'brand', 'кратность', 'min', 'available',
                'price RUB с ндс', 'delivery'],
            ['Название компании#СКЛАД КЛИЕНТА#порядковый номер компонента ', null, 'партномер ', 'описание',
                'год производства компонента (обязательно заполнить)', 'производитель', 'кратность заказа',
                'Минимум к заказу', "Кол-во в наличии\u{00A0}", 'цена с ндс, руб.', 'срок поставки '],
        ];
    }

    public function array(): array
    {
        return array_map(fn($row) => [
            $row['code'], null, $row['title'], $row['description'], $row['dc'], $row['brand'], 1, 1,
            $row['available'], $row['price'], 'склад',
        ], $this->rows);
    }
}
