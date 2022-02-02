<?php

namespace App\Http\Resources;

use App\SellerPriceRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerPriceResource extends JsonResource
{
    private array $fiveSaltySellers;

    public function __construct($resource)
    {
        $this->fiveSaltySellers = [
            config('pricing.Compel.sellerId'),
            config('pricing.DigiKey.sellerId'),
            config('pricing.Rct.sellerId')
        ];
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $rule = $request->rule ?? SellerPriceRule::query()->firstWhere('alias', 'buyer_rule');
        return [
            'name' => $this->sellerWarehouse->sellerGood->name,
            'producer' => $this->sellerWarehouse->sellerGood->producer,
            'case' => $this->sellerWarehouse->sellerGood->case,
            'remark' => $this->sellerWarehouse->sellerGood->remark . ' ' . $this->sellerWarehouse->remark,
            'id' => $this->id,
            'sellerGoodId' => $this->sellerWarehouse->sellerGood->id,
            'code' => $this->sellerWarehouse->sellerGood->code,
            'warehouseCode' => $this->sellerWarehouse->code,
            'goodId' => $this->sellerWarehouse->sellerGood->good_id,
            'sellerId' => $this->sellerWarehouse->sellerGood->seller_id,
            'packageQuantity' => $this->sellerWarehouse->sellerGood->package_quantity,
            'multiplicity' => $this->sellerWarehouse->multiplicity,
            'quantity' => $this->sellerWarehouse->quantity,
            'minQuantity' => $this->min_quantity,
            'maxQuantity' => $this->max_quantity,
            'pos' => $this->sellerWarehouse->sellerGood->pos,
            'price' => $this->priceRule($rule),
            'CharCode' => $this->CharCode,
            'isInput' => $this->isInputRule($rule),
            'deliveryTime' => $this->sellerWarehouse->sellerGood->basic_delivery_time
                + $this->sellerWarehouse->additional_delivery_time,
            'isSomeoneElsesWarehouse' => $this->sellerWarehouse->additional_delivery_time > 0,
            'isApi' => $request->isFile !== 'true',
            'options' => $this->sellerWarehouse->options,
            'updatedAt' => $this->updated_at,
        ];
    }

    private function priceRule($rule)
    {
        switch ($rule->alias) {
            case 'full_rule':
                return $this->value;
            default:
                if (in_array($this->sellerWarehouse->sellerGood->seller_id, $this->fiveSaltySellers)) {
                    return $this->value * 1.15;
                } else {
                    return $this->is_input ? 0 : $this->value;
                }
        }
    }

    private function isInputRule($rule)
    {
        switch ($rule->alias) {
            case 'full_rule':
                return $this->is_input;
            default:
                if (in_array($this->sellerWarehouse->sellerGood->seller_id, $this->fiveSaltySellers)) {
                    return !$this->is_input;
                } else {
                    return $this->is_input;
                }
        }
    }
}
