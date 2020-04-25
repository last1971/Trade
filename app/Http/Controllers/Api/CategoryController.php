<?php


namespace App\Http\Controllers\Api;


use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;

class CategoryController extends ModelController
{
    public function __construct()
    {
        parent::__construct(CategoryService::class, CategoryResource::class);
    }
}
