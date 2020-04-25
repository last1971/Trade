<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'SCODE' => $this->SCODE,
            'POKUPATCODE' => $this->POKUPATCODE,
            'NS' => $this->NS,
            'DATA' => $this->DATA,
            'STATUS' => $this->STATUS,
            'FIRM_ID' => $this->FIRM_ID,
            'STAFF_ID' => $this->STAFF_ID,
            'invoiceLinesCount' => $this->invoiceLinesCount,
            'invoiceLinesSum' => $this->invoiceLinesSum,
            'cashFlowsSum' => $this->cashFlowsSum,
            'transferOutLinesSum' => $this->transferOutLinesSum,
            'buyer' => new BuyerResource($this->buyer),
            'firm' => new FirmResource($this->firm),
            'employee' => new EmployeeResource($this->employee),
        ];
    }
}
