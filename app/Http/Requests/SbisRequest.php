<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SbisRequest extends FormRequest
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
            'buyerId' => 'required|integer',
            'date' => 'required|date',
        ];
    }
}
