<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MpnPartRequest extends FormRequest
{
    /** Столько же букв/цифр требует сам сервис (MIN_MPN_ALNUM в pricing-nest). */
    public const MIN_ALNUM = 4;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Голый MPN: латиница, цифры и разделители. Кириллицу и описания mpn.cc не понимает.
            'q' => 'required|string|max:120|regex:/^[A-Za-z0-9][A-Za-z0-9\-._\/#+]*$/',
            'manufacturer' => 'nullable|string|max:120',
            'refresh' => 'nullable|in:0,1,true,false',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $alnum = preg_replace('/[^A-Za-z0-9]/', '', (string) $this->get('q'));
            if (strlen($alnum) < self::MIN_ALNUM) {
                $validator->errors()->add('q', 'MPN короче ' . self::MIN_ALNUM . ' букв или цифр');
            }
        });
    }
}
