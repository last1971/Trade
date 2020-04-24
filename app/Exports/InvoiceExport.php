<?php

namespace App\Exports;

use App\Http\Requests\IndexRequest;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InvoiceExport implements FromCollection, WithHeadings, WithColumnFormatting, WithMapping
{
    /**
     * @var Builder
     */
    protected $query;

    public function __construct(IndexRequest $request, InvoiceService $service)
    {
        $this->query = $service->index($request)->limit(1000);
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'Дата', 'Номер', 'Покупатель', 'Фирма', 'Статус', 'Строк', 'Сумма', 'Оплачено', 'Отгружено', 'Примечание'
        ];
    }

    public function map($invoice): array
    {
        return [
            Date::dateTimeToExcel(new Carbon($invoice->DATA)),
            $invoice->NS,
            $invoice->buyer->SHORTNAME,
            $invoice->firm->FIRMNAME,
            array('Фрмируется', 'Сформирован', 'Резерв', 'Подборка', 'Подобран', 'Закрыт', 'Kорзина')[$invoice->STATUS],
            $invoice->invoiceLinesCount,
            $invoice->invoiceLinesSum,
            $invoice->cashFlowsSum,
            $invoice->transferOutLinesSum,
            $invoice->PRIM,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
