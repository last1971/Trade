<?php

namespace App\Rules;

use App\TransferOut;

class RightCountryRule extends TransferOutRule
{
    private array $countryCodes;
/**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(TransferOut $transferOut)
    {
        parent::__construct($transferOut);
        $this->countryCodes = config('country_codes');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $ret = true;
        /** @var TransferOut $transferOutLine */
        foreach ($this->transferOut->transferOutLines as $transferOutLine) {
            if (!array_key_exists(\Str::upper($transferOutLine->STRANA), $this->countryCodes)) {
                $this->message = 'Как называется народ проживающий в ' . $transferOutLine->STRANA .
                    '? Позиция: ' . $transferOutLine->good->name->NAME;
                $ret = false;
                break;
            }
        }
        return $ret;
    }
}
