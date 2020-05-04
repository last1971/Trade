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
        $id = $this->route()->parameter('id');
        $invoice = Invoice::with('firm', 'buyer')->findOrFail(intval($id));
        $buyers = $this->user()->userBuyers;
        if ($buyers->isEmpty() || $buyers->firstWhere('buyer_id', $invoice->POKUPATCODE)) {
            $this->merge(compact('invoice'));
            return true;
        }
        return false;
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
            'withStamp' => 'in:true,false'
        ];
    }

    protected function passedValidation()
    {
        $this->merge([
            'withVAT' => $this->withVAT === 'true',
            'withStamp' => $this->withStamp === 'true',
        ]);
    }
}