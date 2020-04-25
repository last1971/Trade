<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuyerResource extends JsonResource
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
            'POKUPATCODE' => $this->POKUPATCODE,
            'FULLNAME' => $this->FULLNAME,
            'SHORTNAME' => $this->SHORTNAME,
            'EMAIL' => $this->EMAIL,
            'ADDRESS' => $this->ADDRESS,
            'PHONES' => $this->PHONES,
            'FAX' => $this->FAX,
            'INN' => $this->INN,
        ];
    }
}
