<?php

namespace App\Http\Requests;

use App\SellerPriceRule;
use Illuminate\Foundation\Http\FormRequest;

class SellerPriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'search' => 'required|string|min:3',
            'isUpdate' => 'nullable|in:true,false',
            'sellerId' => 'required_without:sellerIds|integer',
            'sellerIds' => 'required_without:sellerId|array',
            'sellerIds.*' => 'integer',
        ];
    }

    public function passedValidation()
    {
        $this->merge([
            'isUpdate' => $this->isUpdate === 'true',
            'sellerPriceRule' => SellerPriceRule::userSellerPriceRule(),
        ]);
    }
}
