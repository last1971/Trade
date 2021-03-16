<?php


namespace App\Services;


use App\FirmHistory;

class FirmHistoryService extends ModelService
{
    public function __construct()
    {
        parent::__construct(FirmHistory::class);
    }
}
