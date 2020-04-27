<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRequest;
use App\Http\Requests\ModelRequest;
use Error;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ModelController extends Controller
{
    protected $service;

    protected $resource;

    /**
     * ModelController constructor.
     * @param $service
     */
    public function __construct($service, $resource = null)
    {
        $this->service = new $service;
        $this->resource = $resource;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(IndexRequest $request)
    {
        //
        $permission = Str::before($request->route()->getName(), '.') . '.full';
        $res = $this->service->index($request)->paginate($request->itemsPerPage);
        if ($request->user()->can($permission) || !$this->resource) {
            return $res;
        }
        return $this->resource::collection($res);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ModelRequest $request
     * @return Response
     */
    public function store(ModelRequest $request)
    {
        //
        return $this->service->create($request);
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
        $permission = Str::before($request->route()->getName(), '.') . '.full';
        $res = $this->service->index($request)->find(intval($id));
        throw_if(!$res, new Error('Запись отуствует в аналах'));
        if ($request->user()->can($permission) || !$this->resource) {
            return $res;
        }
        return new $this->resource($res);
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
