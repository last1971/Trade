<?php


namespace App\Services;


use App\Employee;

class EmployeeService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Employee::class);
    }
}
