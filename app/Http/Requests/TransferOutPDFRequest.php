<?php

namespace App\Http\Requests;

use App\Rules\RightCountryRule;
use App\Rules\RightUnitRule;
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

    protected function prepareForValidation()
    {
        if ($this->has('body')) {
           $this->merge(['body' => $this->body === 'true']);
        }
        if ($this->has('producer')) {
            $this->merge(['producer' => $this->producer === 'true']);
        }
        if ($this->has('category')) {
            $this->merge(['category' => $this->category === 'true']);
        }
        if ($this->has('divider')) {
            $this->merge(['divider' => $this->divider === 'true']);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'body' => 'boolean',
            'producer' => 'boolean',
            'category' => 'boolean',
            'divider' => ['boolean', new RightCountryRule($this->transferOut), new RightUnitRule($this->transferOut)],
        ];
    }
}
