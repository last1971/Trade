<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceExportRecource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'NS' => $this->NS,
            'DATA' => (new Carbon($this->DATA))->format('d.m.Y'),
            'POKUPAT' => $this->buyer->SHORTNAME,
            'FIRM' => $this->firm->FIRMNAME,
            'STATUS' => $this->STATUS,
            'invoiceLinesCount' => $this->invoiceLinesCount,
            'invoiceLinesSum' => $this->invoiceLinesSum,
            'cashFlowsSum' => $this->cashFlowsSum,
            'transferOutLinesSum' => $this->transferOutLinesSum,
            'STAFF' => $this->employee->FULLNAME,
        ];
    }
}
