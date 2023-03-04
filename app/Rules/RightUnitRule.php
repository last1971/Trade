<?php

namespace App\Rules;

use App\TransferOut;
use App\UnitCodeAlias;
use Carbon\Carbon;

class RightUnitRule extends TransferOutRule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(TransferOut $transferOut)
    {
        parent::__construct($transferOut);
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
            $alias = UnitCodeAlias::rmember($transferOutLine->good->UNIT_I);
            if (!$alias) {
                $this->message = 'Давай в крокодилах считать будем ' . $transferOutLine->good->name->NAME .
                ', a не в ' . $transferOutLine->good->UNIT_I;
                $ret = false;
                break;
            }
        }
        return $ret;
    }
}
