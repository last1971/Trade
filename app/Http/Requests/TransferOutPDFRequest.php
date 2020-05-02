<?php

namespace App\Http\Requests;

use App\TransferOut;
use Illuminate\Foundation\Http\FormRequest;

class TransferOutPDFRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $id = $this->route()->parameter('id');
        $transferOut = TransferOut::with('firm', 'buyer.advancedBuyer', 'employee', 'invoice.cashFlows')
            ->findOrFail(intval($id));
        $buyers = $this->user()->userBuyers;
        if ($buyers->isEmpty() || $buyers->firstWhere('buyer_id', $transferOut->POKUPATCODE)) {
            $this->merge(compact('transferOut'));
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

        ];
    }
}
