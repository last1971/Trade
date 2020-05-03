<?php

namespace App\Exports;

use App\Http\Requests\SbisRequest;
use App\Services\TransferOutLineService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SbisExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @var Builder
     */
    protected $query;

    /**
     * SbisExport constructor.
     * @param SbisRequest $request
     * @param TransferOutLineService $service
     */
    public function __construct(SbisRequest $request, TransferOutLineService $service)
    {
        $this->query = $service->index(collect([
            'with' => ['transferOut.invoice', 'name', 'invoiceLine'],
            'filterAttributes' => ['transferOut.POKUPATCODE', 'transferOut.DATA'],
            'filterOperators' => ['=', 'BETWEENDATE'],
            'filterValues' => [$request->buyerId, [$request->date, Carbon::create($request->date)->addDay()]],
        ]));
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        //
        return $this->query->get();
    }

    public function headings(): array
    {
        return ['Номер счета', 'Номер заявки', 'Номер  УПД', 'Наименование', 'Количество', 'Срок'];
    }

    public function map($row): array
    {
        return [
            $row->transferOut->invoice->NS,
            $row->transferOut->invoice->NZ,
            $row->transferOut->NSF,
            $row->name->NAME,
            $row->QUAN,
            $row->invoiceLine->PRIM1,
        ];
    }
}
