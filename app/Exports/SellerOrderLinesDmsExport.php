<?php

namespace App\Exports;

use App\SellerOrderLine;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SellerOrderLinesDmsExport implements FromCollection, WithHeadings, WithColumnFormatting, WithMapping
{
    protected $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return SellerOrderLine::where('seller_order_id', $this->orderId)->get();
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function headings(): array
    {
        return [
            '№',
            'Артикул',
            'Наименование',
            'Количество',
            'Цена',
            'Сумма',
        ];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $row->item_id,
            $row->item_name,
            $row->qty,
            $row->price,
            $row->price * $row->qty,
        ];
    }
}
