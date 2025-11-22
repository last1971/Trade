<?php

namespace App\Exports;

use App\OrderLine;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SellerOrderLinesExport implements FromCollection, WithHeadings, WithColumnFormatting, WithMapping
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
        return OrderLine::where('MASTER_ID', $this->orderId)
            ->with('good')
            ->get();
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function headings(): array
    {
        return [
            '№',
            'Артикул',
            'Наименование',
            'Количество',
            'Примечание',
        ];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $row->good->ARTICUL ?? '',
            $row->NAME_IN_PRICE,
            $row->QUAN,
            $row->PRIM ?? '',
        ];
    }
}
