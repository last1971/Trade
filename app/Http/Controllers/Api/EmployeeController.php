<?php


namespace App\Http\Controllers\Api;


use App\Services\EmployeeService;

class EmployeeController extends ModelController
{
    public function __construct()
    {
        parent::__construct(EmployeeService::class);
    }
}
