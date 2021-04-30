<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SellerPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'name' => $this->sellerWarehouse->sellerGood->name,
            'producer' => $this->sellerWarehouse->sellerGood->producer,
            'case' => $this->sellerWarehouse->sellerGood->case,
            'remark' => $this->sellerWarehouse->sellerGood->remark . ' ' . $this->sellerWarehouse->remark,
            'id' => $this->id,
            'code' => $this->sellerWarehouse->sellerGood->code,
            'goodId' => $this->sellerWarehouse->sellerGood->good_id,
            'sellerId' => $this->sellerWarehouse->sellerGood->seller_id,
            'packageQuantity' => $this->sellerWarehouse->sellerGood->package_quantity,
            'multiplicity' => $this->sellerWarehouse->multiplicity,
            'quantity' => $this->sellerWarehouse->quantity,
            'minQuantity' => $this->min_quantity,
            'maxQuantity' => $this->max_quantity,
            'price' => $this->value,
            'CharCode' => $this->CharCode,
            'isInput' => $this->is_input,
            'deliveryTime' => $this->sellerWarehouse->sellerGood->basic_delivery_time
                + $this->sellerWarehouse->additional_delivery_time,
            'isSomeoneElsesWarehouse' => $this->sellerWarehouse->additional_delivery_time > 0,
            'isApi' => $request->isFile !== 'true',
            'options' => $this->sellerWarehouse->options,
            'updatedAt' => $this->updated_at,
        ];
    }
}
