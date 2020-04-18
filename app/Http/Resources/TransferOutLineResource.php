<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferOutLineResource extends JsonResource
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
            'REALPRICEFCODE' => $this->REALPRICEFCODE,
            'SFCODE' => $this->SFCODE,
            'GOODSCODE' => $this->GOODSCODE,
            'PRICE' => $this->PRICE,
            'QUAN' => $this->QUAN,
            'REALPRICECODE' => $this->REALPRICECODE,
            'GTD' => $this->GTD,
            'STRANA' => $this->STRANA,
            'SUMMAP' => $this->SUMMAP,
            'category' => new CategoryResource($this->category),
            'good' => new GoodResource($this->good),
            'name' => new NameResource($this->name),
        ];
    }
}
