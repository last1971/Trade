<?php


namespace App\Services;


use App\Name;

class NameService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Name::class);
    }
}
