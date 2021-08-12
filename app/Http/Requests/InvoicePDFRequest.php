<?php

namespace App\Http\Requests;

use App\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class InvoicePDFRequest extends FormRequest
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
        $ret = $user->can($name);

        $id = $this->route()->parameter('id');
        $invoice = Invoice::with('firm', 'buyer', 'firmHistory')->findOrFail(intval($id));
        $buyers = $this->user()->userBuyers;
        if ($buyers->isEmpty() || $buyers->firstWhere('buyer_id', $invoice->POKUPATCODE)) {
            $this->merge(compact('invoice'));
        } else {
            $ret = false;
        }
        return $ret;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'withVAT' => 'in:true,false',
            'withStamp' => 'in:true,false',
            'withFooter' => 'in:true,false',
            'newAccount' => 'in:true,false',
            'body' => 'in:true,false',
            'producer' => 'in:true,false',
            'category' => 'in:true,false',
            'divider' => 'in:true,false',
            'deliveryTime' => 'in:true,false',
            'sortBy' => 'array',
            'sortBy.*' => 'string',
            'sortDesc' => 'required_with:sortBy|array',
            'sortDesc.*' => 'required_with:sortBy|in:true,false',
        ];
    }

    protected function passedValidation()
    {
        if (is_array($this->sortDesc)) {
            $this->merge(['sortDesc' => array_map(function ($v) {
                return $v === 'true';
            }, $this->sortDesc)]);
        }
        $this->merge([
            'withVAT' => $this->withVAT === 'true',
            'withStamp' => $this->withStamp === 'true',
            'withFooter' => $this->withFooter === 'true',
            'newAccount' => $this->newAccount === 'true',
            'body' => $this->body === 'true',
            'producer' => $this->producer === 'true',
            'category' => $this->category === 'true',
            'divider' => $this->divider === 'true',
            'deliveryTime' => $this->deliveryTime === 'true',
        ]);
    }
}
