<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceLineResource extends JsonResource
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
            'REALPRICECODE' => $this->REALPRICECODE,
            'SCODE' => $this->SCODE,
            'GOODSCODE' => $this->GOODSCODE,
            'PRICE' => $this->PRICE,
            'QUAN' => $this->QUAN,
            'SUMMAP' => $this->SUMMAP,
            'PRIM' => $this->PRIM,
            'STAFF_ID' => $this->STAFF_ID,
            'reservesQuantity' => $this->reservesQuantity,
            'pickUpsQuantity' => $this->pickUpsQuantity,
            'transferOutLinesQuantity' => $this->transferOutLinesQuantity,
            'category' => new CategoryResource($this->category),
            'good' => new GoodResource($this->good),
            'name' => new NameResource($this->name),
            'invoice' => new InvoiceResource($this->invoice),
        ];
    }

}
