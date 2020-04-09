<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
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
            //
            'with' => 'array',
            'with.*' => 'string',
            'selectAttributes' => 'array',
            'selectAttributes.*' => 'string',
            'filterAttributes' => 'array',
            'filterAttributes.*' => 'string',
            'filterOperators' => 'required_with:filterAttributes|array',
            'filterOperators.*' => 'required_with:filterAttributes|string',
            'filterValues' => 'required_with:filterAttributes|array',
            // 'filterValues.*' => 'required_with:filterAttributes|string|array',
            'sortBy' => 'array',
            'sortBy.*' => 'string',
            'sortDesc' => 'required_with:sortBy|array',
            'sortDesc.*' => 'required_with:sortBy|boolean',
        ];
    }

    /**
     *  Mutate request before validation
     */
    protected function prepareForValidation()
    {
        if (is_array($this->sortDesc)) {
            $this->merge(['sortDesc' => array_map(function ($v) {
                return $v === 'true';
            }, $this->sortDesc)]);
        }
        if (is_array($this->filterValues)) {
            $this->merge(['filterValues' => array_map(function ($v) {
                if (is_numeric($v) && intval($v) == $v) {
                    return intval($v);
                } else {
                    return $v;
                }
            }, $this->filterValues)]);
        }
    }
}
