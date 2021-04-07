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
            'sortDesc.*' => 'required_with:sortBy|in:true,false',
        ];
    }

    /**
     *  Mutate request after validation
     */
    protected function passedValidation()
    {
        if (is_array($this->sortDesc)) {
            $this->merge(['sortDesc' => array_map(function ($v) {
                return $v === 'true';
            }, $this->sortDesc)]);
        }
        if (is_array($this->filterValues)) {
            $filterValues = $this->filterValues;
            array_walk($filterValues, function (&$v, $i) {
                if (is_numeric($v) && intval($v) == $v) {
                    $v = intval($v);
                } else if ($this->filterOperators[$i] === 'IN') {
                    $v = json_decode($v);
                }
            });
            $this->merge(compact('filterValues'));
        }
        if (is_array($this->filterAttributes)) {
            $index = array_search('name.NAME', $this->filterAttributes);
            if ($index !== false) {
                $filterValues = $this->filterValues;
                $filterValues[$index] = mb_substr($filterValues[$index], 0, 70);
                $this->merge(compact('filterValues'));
            }
        }
    }
}
