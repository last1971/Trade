<?php

namespace App\Http\Requests;

class RetailPriceRequest extends ModelRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'item' => 'array|required',
            'item.PRICEROZN' => 'required|numeric',
            'item.PRICEMOPT' => [function ($attribute, $value, $fail) {
                if ($value && $value > $this->item['PRICEROZN'])
                    $fail($attribute . ' is invalid.');
            }],
            'options.with' => 'array',
            'options.with.*' => 'string',
        ];
    }
}
