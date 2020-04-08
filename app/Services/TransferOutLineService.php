<?php


namespace App\Services;


use App\TransferOutLine;

class TransferOutLineService extends ModelService
{
    public function __construct()
    {
        parent::__construct(TransferOutLine::class);
    }
}
