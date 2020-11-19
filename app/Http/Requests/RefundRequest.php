<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
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
            'selectedIds' => 'required|array',
            'selectedIds.*' => 'integer',
            'selectedQnts' => 'required|array',
            'selectedQnts.*' => 'integer',
            'datatime' => 'string',
            'items' => 'required|array',
            'items.*.name' => 'string|required',
            'items.*.quantity' => 'integer|required',
            'items.*.price' => 'numeric|required',
            'items.*.amount' => 'numeric|required',
            'amount' => 'numeric|required',
        ];
    }
}
