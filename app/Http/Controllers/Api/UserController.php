<?php

namespace App\Http\Controllers\Api;

use App\Services\UserService;

class UserController extends ModelController
{
    public function __construct()
    {
        parent::__construct(UserService::class);
    }
}
