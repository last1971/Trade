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
        $invoice = Invoice::with('firm', 'buyer', 'firmHistory')->findOrFail(intval($id));
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
            'withStamp' => 'in:true,false',
            'newAccount' => 'in:true,false',
            'body' => 'in:true,false',
            'producer' => 'in:true,false',
            'category' => 'in:true,false',
            'divider' => 'in:true,false',
            'deliveryTime' => 'in:true,false',
        ];
    }

    protected function passedValidation()
    {
        $this->merge([
            'withVAT' => $this->withVAT === 'true',
            'withStamp' => $this->withStamp === 'true',
            'newAccount' => $this->newAccount === 'true',
            'body' => $this->body === 'true',
            'producer' => $this->producer === 'true',
            'category' => $this->category === 'true',
            'divider' => $this->divider === 'true',
            'deliveryTime' => $this->deliveryTime === 'true',
        ]);
    }
}
