<?php

namespace App\Rules;

use App\TransferOut;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class TransferOutRule implements Rule
{
    protected TransferOut $transferOut;

    protected string $message;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(TransferOut $transferOut)
    {
        $this->transferOut = $transferOut;
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
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message . '. УПД № ' . $this->transferOut->NSF . ' от '
            . Carbon::parse($this->transferOut->DATA)->format('d.m.Y');
    }
}
