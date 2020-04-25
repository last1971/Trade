<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRequest;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(IndexRequest $request)
    {
        return Role::query()->paginate(10);
    }
}
