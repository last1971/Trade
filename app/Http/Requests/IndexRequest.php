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
            'sortDesc.*' => 'required_with:sortBy|in:asc,desc',
        ];
    }
}
