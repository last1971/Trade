<?php


namespace App\Http\Controllers\Api;


use App\Http\Resources\NameResource;
use App\Services\NameService;

class NameController extends ModelController
{
    public function __construct()
    {
        parent::__construct(NameService::class, NameResource::class);
    }
}
