<?php


namespace App\Services;


use App\Category;

class CategoryService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Category::class);
    }
}
