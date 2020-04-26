<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class ModelRequest extends FormRequest
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
        return config('rules')[$this->route()->getName()];
    }

    /**
     *  Mutate request after validation
     */
    protected function passedValidation()
    {
        $item = $this->item;
        if (isset($item['DATA'])) {
            $item['DATA'] = DB::raw($item['DATA']);
        }
        if (isset($item['QUAN'])) {
            $item['QUAN'] = intval($item['QUAN']);
        }
        if (isset($item['PRICE'])) {
            $item['PRICE'] = DB::raw($item['PRICE']);
        }
        if (isset($item['SUMMAP'])) {
            $item['SUMMAP'] = DB::raw($item['SUMMAP']);
        }
        $this->merge(compact('item'));
    }
}
