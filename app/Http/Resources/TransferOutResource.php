<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferOutResource extends JsonResource
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
            'SFCODE' => $this->SFCODE,
            'POKUPATCODE' => $this->POKUPATCODE,
            'NSF' => $this->NSF,
            'DATA' => $this->DATA,
            'SCODE' => $this->SCODE,
            'FIRM_ID' => $this->FIRM_ID,
            'STAFF_ID' => $this->STAFF_ID,
            'transferOutLinesCount' => $this->transferOutLinesCount,
            'transferOutLinesSum' => $this->transferOutLinesSum,
            'buyer' => new BuyerResource($this->buyer),
            'firm' => new FirmResource($this->firm),
            'employee' => new EmployeeResource($this->employee),
            'invoice' => new InvoiceResource($this->invoice),
        ];
    }
}
