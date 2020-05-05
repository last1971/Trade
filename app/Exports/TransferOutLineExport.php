<?php


namespace App\Exports;


use App\Http\Requests\IndexRequest;
use App\Services\TransferOutLineService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use VAT;

class TransferOutLineExport implements FromCollection, WithHeadings, WithColumnFormatting, WithMapping
{
    /**
     * @var Builder
     */
    protected $query;

    public function __construct(IndexRequest $request, TransferOutLineService $service)
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
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function headings(): array
    {
        return [
            'Дата',
            'УПД',
            'Покупатель',
            'Категория',
            'Название',
            'Корпус',
            'Производитель',
            'Кол.-во',
            'Цена без НДС',
            'НДС',
            'Сумма',
            'Страна',
            'ГТД',
            'Цена с НДС'
        ];
    }

    public function map($row): array
    {
        return [
            Date::dateTimeToExcel(new Carbon($row->transferOut['DATA'])),
            $row->transferOut->NSF,
            $row->transferOut->buyer->SHORTNAME,
            $row->category->CATEGORY,
            $row->name->NAME,
            $row->good->BODY,
            $row->good->PRODUCER,
            $row->QUAN,
            $row->SUMMAP / (100 + VAT::get($row->transferOut['DATA'])) * 100 / $row->QUAN,
            $row->SUMMAP - $row->SUMMAP / (100 + VAT::get($row->transferOut['DATA'])) * 100,
            $row->SUMMAP,
            $row->STRANA,
            $row->GTD,
            $row->PRICE
        ];
    }
}
