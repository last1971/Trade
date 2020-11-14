<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoodsListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $name = $this->route()->getName();
        $user = $this->user();
        return $user->can($name);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'lines' => 'array|required',
            'lines.*.GOODSCODE' => 'integer|required',
            'lines.*.name' => 'string|required',
            'lines.*.quantity' => 'integer|required',
            'lines.*.price' => 'numeric|required',
            'lines.*.amount' => 'numeric|required',
            'lines.*.retailOrderLineId' => 'nullable|integer',
            'buyerId' => 'nullable|integer',
            'paymentType' => 'string|in:cash,electronically,black'
        ];
    }
}
