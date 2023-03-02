<?php

namespace App\Rules;

use App\TransferOut;
use Carbon\Carbon;

class RightUnitRule extends TransferOutRule
{
    private array $units;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(TransferOut $transferOut)
    {
        parent::__construct($transferOut);
        $this->units = config('unit_codes');
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
            if (!array_key_exists(\Str::upper($transferOutLine->good->UNIT_I), $this->units)) {
                $this->message = 'Давай в крокодилах считать будем ' . $transferOutLine->good->name->NAME .
                ', a не в ' . $transferOutLine->good->UNIT_I;
                $ret = false;
                break;
            }
        }
        return $ret;
    }
}
