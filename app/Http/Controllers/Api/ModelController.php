<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRequest;
use App\Http\Requests\ModelRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ModelController extends Controller
{
    protected $service;

    /**
     * ModelController constructor.
     * @param $service
     */
    public function __construct($service)
    {
        $this->service = new $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(IndexRequest $request)
    {
        //
        return $this->service->index($request)->paginate($request->itemsPerPage);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id, IndexRequest $request)
    {
        //
        return $this->service->index($request)->find(intval($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(ModelRequest $request, $id)
    {
        //
        return $this->service->update($request, $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
