<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLineResource extends JsonResource
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
            'ID' => $this->ID,
            'MASTER_ID' => $this->MASTER_ID,
            'GOODSCODE' => $this->GOODSCODE,
            'QUAN' => $this->QUAN,
            'DATA_PRIH' => $this->DATA_PRIH ?? $this->order->DATA_PRIH,
            'storeLinesQuantity' => $this->storeLinesQuantity,
            'shopLinesQuantity' => $this->shopLinesQuantity
        ];
    }
}
