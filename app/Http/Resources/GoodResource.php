<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodResource extends JsonResource
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
            'GOODSCODE' => $this->GOODSCODE,
            'NAMECODE' => $this->NAMECODE,
            'CATEGORYCODE' => $this->CATEGORYCODE,
            'PRODUCER' => $this->PRODUCER,
            'BODY' => $this->BODY,
            'PRIM' => $this->PRIM,
            'DESCRIPTION' => $this->DESCRIPTION,
        ];
    }
}
