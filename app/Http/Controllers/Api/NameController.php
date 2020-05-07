<?php


namespace App\Http\Controllers\Api;


use App\Http\Requests\ModelRequest;
use App\Http\Resources\NameResource;
use App\Services\NameService;

class NameController extends ModelController
{
    public function __construct()
    {
        parent::__construct(NameService::class, NameResource::class);
    }

    public function store(ModelRequest $request)
    {
        $request->validate([
            'item.NAME' => 'unique:firebird.NAME,NAME,NULL,id,CATEGORYCODE,' . $request->item['CATEGORYCODE']
        ]);
        return parent::store($request);
    }
}
