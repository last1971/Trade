<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NameResource extends JsonResource
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
            'NAMECODE' => $this->NAMECODE,
            'NAME' => $this->NAME,
            'SERIA' => $this->SERIA,
            'CATEGORYCODE' => $this->CATEGORYCODE,
        ];
    }
}
