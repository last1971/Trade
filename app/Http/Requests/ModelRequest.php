<?php

namespace App\Http\Requests;

use App\Name;
use Error;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    private function retailPrice()
    {
        return [
            'item.GOODSCODE' => 'required|integer',
            'item.PRICEROZN' => 'required|numeric',
            'item.QUANMOPT' => ['nullable', 'integer', function ($attribute, $value, $fail) {
                if ($value && $value < 2)
                    $fail('validation.invalid');
            }],
            'item.PRICEMOPT' => ['nullable', 'numeric', function ($attribute, $value, $fail) {
                if ($value && ($value >= $this->item['PRICEROZN'] || !$this->item['QUANMOPT']))
                    $fail('validation.invalid');
            }],
            'item.QUANOPT' => ['nullable', 'integer', function ($attribute, $value, $fail) {
                if ($value && ($value < 2 || $value <= $this->item['QUANMOPT']))
                    $fail('validation.invalid');
            }],
            'item.PRICEOPT' => ['nullable', 'numeric', function ($attribute, $value, $fail) {
                if ($value &&
                    (!$this->item['QUANOPT'] ||
                        $value >= $this->item['PRICEROZN'] ||
                        $value >= $this->item['PRICEMOPT']
                    )
                )
                    $fail('validation.invalid');
            }],
        ];
    }

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
        $rules = [
            'item' => 'array|required',
            'options.with' => 'array',
            'options.with.*' => 'string',
        ];
        switch ($this->route()->getName()) {
            case 'advanced-buyer.destroy':
            case 'order-line.destroy':
            case 'invoice-line.destroy':
                $rules = [];
                break;
            case 'advanced-buyer.store':
            case 'advanced-buyer.update':
                $rules += [
                    'item.edo_id' => 'required|string|unique:App\AdvancedBuyer,edo_id',
                    'item.buyer_id' => 'required|integer|unique:App\AdvancedBuyer,buyer_id',
                ];
                break;
            case 'good.store':
            case 'good.update':
                $rules += [
                    'item.NAMECODE' => 'required|integer',
                    'item.CATEGORYCODE' => 'required|integer',
                    'item.UNIT_I' => 'required|string|max:10',
                    'item.PRODUCER' => 'nullable|string|max:60',
                    'item.BODY' => 'nullable|string|max:60',
                    'item.PRIM' => 'nullable|string|max:60',
                ];
                break;
            case 'invoice.receipt':
                break;
            case 'invoice.update':
                $rules += [
                    'item.STATUS' => 'integer',
                    'item.DATA' => 'date',
                    'item.NS' => 'integer',
                    'item.FIRM_ID' => 'integer',
                    'item.FIRMS_HISTORY_ID' => 'nullable|integer',
                    'item.POKUPATCODE' => 'integer',
                    'item.PRIM' => 'nullable|string',
                    'item.IGK' => 'nullable|string',
                    'item.STAFF_ID' => 'nullable|integer',
                ];
                break;
            case 'invoice.store':
                $rules += [
                    'item.STATUS' => 'integer',
                    'item.DATA' => 'date',
                    'item.FIRM_ID' => 'integer',
                    'item.FIRMS_HISTORY_ID' => 'nullable|integer',
                    'item.POKUPATCODE' => 'integer',
                    'item.PRIM' => 'nullable|string',
                    'item.IGK' => 'nullable|string',
                    'item.STAFF_ID' => 'nullable|integer',
                ];
                break;
            case 'invoice-line.store':
                $rules += [
                    'item.SCODE' => 'integer',
                    'item.GOODSCODE' => 'integer',
                    'item.QUAN' => 'integer',
                    'item.PRICE' => 'numeric',
                    'item.SUMMAP' => 'numeric',
                    'item.PRIM' => 'nullable|string',
                    'item.WHERE_ORDERED' => 'nullable|string',
                ];
                break;
            case 'invoice-line.update':
                $rules += [
                    'item.QUAN' => 'integer',
                    'item.PRICE' => 'numeric',
                    'item.SUMMAP' => 'numeric',
                ];
                break;
            case 'name.store':
            case 'name.update':
                $rules += [
                    'item.CATEGORYCODE' => 'integer|required',
                    'item.NAME' => [
                        'required',
                        'string',
                        function ($attribute, $value, $fail) {
                            if (Name::query()
                                ->where('NAME', $value)
                                ->where('CATEGORYCODE', intval($this->item['CATEGORYCODE']))
                                ->count() > 0)
                                $fail('validation.invalid');
                        }
                    ],
                    'item.SERIA' => 'required|string',
                ];
                break;
            case 'order.store':
                $rules += [
                    'item.STATUS' => 'integer',
                    'item.INVOICE_DATA' => 'required|date',
                    'item.DATA_PRIH' => 'required|date',
                    'item.INVOICE_NUM' => 'required|string',
                    'item.WHEREISPOSTCODE' => 'required|integer',
                ];
                break;
            case 'order.update':
                $rules += [
                    'item.STATUS' => 'integer',
                    'item.INVOICE_DATA' => 'date',
                    'item.DATA_PRIH' => 'date',
                    'item.INVOICE_NUM' => 'string',
                    'item.WHEREISPOSTCODE' => 'integer',
                ];
                break;
            case 'order-line.store':
                $rules += [
                    'item.MASTER_ID' => 'required|integer'  ,
                ];
            case 'order-line.update':
                $rules += [
                    'item.QUAN' => 'integer',
                    'item.PRICE' => 'numeric',
                    'item.SUMMAP' => 'numeric',
                    'item.STRANA' => 'nullable|string',
                    'item.GTD' => 'nullable|string',
                    'item.PRIM' => 'nullable|string',
                ];
                break;
            case 'order-step.store':
            case 'order-step.update':
                $rules += [
                    'item.GOODSCODE' => 'required|integer',
                    'item.BOUND_QUAN_SHOP' => 'integer',
                    'item.BOUND_QUAN_SKLAD' => 'nullable|integer',
                    'item.QUAN_TO_ZAKAZ_SHOP' => 'integer',
                    'item.QUAN_TO_ZAKAZ_SKLAD' => 'nullable|integer',
                ];
                break;
            case 'payment.store':
            case 'payment.update':
                $rules += [
                    'item.date' => 'required|date',
                    'item.number' => 'nullable|string',
                    'item.seller_id' => 'required|integer',
                    'item.amount' => 'required|numeric',
                    'item.weight' => 'required|integer',
                    'item.pay_before' => 'nullable|date',
                    'item.comment' => 'nullable|string',
                ];
                break;
            case 'payment-order.store':
            case 'payment-order.update':
                $rules += [
                    'item.date' => 'required|date',
                    'item.number' => 'nullable|string',
                    'item.payment_id' => 'required|exists:payments,id',
                    'item.amount' => 'required|numeric',
                ];
                break;
            case 'reserve.update':
                $rules += ['item.QUANSKLAD' => 'required|integer'];
                break;
            case 'retail-price.store':
            case 'retail-price.update':
                $rules += $this->retailPrice();
                break;
            case 'seller.store':
            case 'seller.update':
                $rules += [
                    'item.NAMEPOST' => 'string|max:60',
                    'item.INN' => 'nullable|string|max:60',
                    'item.EMAIL' => 'nullable|email',
                ];
                break;
            case 'transfer-out.store':
                $rules += [
                    'item.SCODE' => 'integer|exists:App\Invoice,SCODE',
                ];
                break;
            case 'transfer-out.update':
                $rules += [
                    'item.PRIM' => 'nullable|string',
                ];
                break;
            case 'transfer-out-line.update':
                $rules += [];
                break;
            case 'user.update':
                $rules += [
                    'item.name' => 'string|required',
                    'item.employeeId' => 'integer',
                    'item.rules' => 'array',
                ];
                break;
            default:
                throw new Error('Need validation rules for ' . $this->route()->getName());
        }
        return $rules;
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
                $item[$value] = floatval($item[$value]);
            }
        }
        if (Str::startsWith($this->route()->getName(), 'invoice.')) {
            if (!$this->user()->hasRole('admin') && array_key_exists('STAFF_ID', $item)) {
                unset($item['STAFF_ID']);
            }
        }
        $this->merge(compact('item'));
    }
}
