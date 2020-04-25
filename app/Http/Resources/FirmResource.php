<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FirmResource extends JsonResource
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
            'FIRM_ID' => $this->FIRM_ID,
            'FIRMNAME' => $this->FIRMNAME,
            'INN' => $this->INN,
        ];
    }
}
