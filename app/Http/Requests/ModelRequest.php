<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class ModelRequest extends FormRequest
{
    private $integerAttributes = [
        'QUAN',
        'QUANOPT',
        'QUANMOPT',
        'CATEGORYCODE',
        'NAMECODE',
        'GOODSCODE',
        'BOUND_QUAN_SKLAD',
        'BOUND_QUAN_SHOP',
        'QUAN_TO_ZAKAZ_SKLAD',
        'QUAN_TO_ZAKAZ_SHOP',
    ];

    private $numericAttributes = ['PRICEROZN', 'PRICEOPT', 'PRICEMOPT', 'PRICE', 'SUMMAP'];

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
        foreach ($this->integerAttributes as $value) {
            if (isset($item[$value]) && $item[$value] !== NULL) {
                $item[$value] = intval($item[$value]);
            }
        }
        foreach ($this->numericAttributes as $value) {
            if (isset($item[$value]) && $item[$value] !== NULL) {
                $item[$value] = DB::raw($item[$value]);
            }
        }
        $this->merge(compact('item'));
    }
}
