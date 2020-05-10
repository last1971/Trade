<?php


namespace App\Services;

use App\Reserve;

class ReserveService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Reserve::class);
        $this->whereAttributes['QUANSHOP+QUANSKLAD'] = 'QUANSHOP+QUANSKLAD > 0';
    }
}
