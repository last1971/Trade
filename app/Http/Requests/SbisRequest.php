<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $isUpd2 = $this->input('type') === 'upd2';

        return [
            'type' => ['nullable', 'string', Rule::in(['upd', 'upd2'])],
            'buyerId' => [Rule::requiredIf(!$isUpd2), 'integer'],
            'date' => [Rule::requiredIf(!$isUpd2), 'date'],
            'invoice_id' => [Rule::requiredIf($isUpd2), 'integer'],
        ];
    }
}
