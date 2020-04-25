<?php


namespace App\Services;


use App\Firm;

class FirmService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Firm::class);
    }
}
