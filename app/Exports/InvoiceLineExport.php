<?php

namespace App\Exports;

use App\Http\Requests\IndexRequest;
use App\Services\InvoiceLineService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InvoiceLineExport implements FromCollection, WithHeadings, WithColumnFormatting, WithMapping
{
    /**
     * @var Builder
     */
    protected $query;

    public function __construct(IndexRequest $request, InvoiceLineService $service)
    {
        $this->query = $service->index($request)->limit(1000);
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        //
        return $this->query->get();
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'H' => NumberFormat::FORMAT_NUMBER,
            'I' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function headings(): array
    {
        return [
            'Дата',
            'Счет',
            'Покупатель',
            'Категория',
            'Название',
            'Корпус',
            'Производитель',
            'Кол.-во',
            'Резерв',
            'Подобрано',
            'В УПД',
            'Цена',
            'Сумма',
            'Срок'
        ];
    }

    public function map($row): array
    {
        return [
            Date::dateTimeToExcel(new Carbon($row->invoice['DATA'])),
            $row->invoice->NS,
            $row->invoice->buyer->SHORTNAME,
            $row->category->CATEGORY,
            $row->name->NAME,
            $row->good->BODY,
            $row->good->PRODUCER,
            $row->QUAN,
            $row->reservesQuantity,
            $row->pickUpsQuantity,
            $row->transferOutLinesQuantity,
            $row->PRICE,
            $row->SUMMAP,
            $row->PRIM
        ];
    }
}
