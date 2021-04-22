<?php

namespace App\Http\Requests;

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
            'isFile' => 'nullable|in:true,false',
            'isUpdate' => 'nullable|in:true,false',
            'sellerId' => 'required|integer',
            'isInput' => 'nullable|in:true,false',
        ];
    }

    public function passedValidation()
    {
        $isFile = $this->isFile === 'true';
        $isUpdate = $this->isUpdate === 'true';
        $isInput = $this->isInput === 'true';
        $this->merge(compact('isFile', 'isInput', 'isUpdate'));
    }
}
