<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRequest;
use App\Services\TransferOutService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TransferOutController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param IndexRequest $request
     * @param TransferOutService $service
     * @return LengthAwarePaginator
     */
    public function index(IndexRequest $request, TransferOutService $service)
    {
        //
        return $service->index($request)->paginate($request->itemsPerPage);
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
     * @param $id
     * @param IndexRequest $request
     * @param TransferOutService $service
     * @return Builder|Builder[]|Collection|Model|mixed|mixed[]|null
     */
    public function show($id, IndexRequest $request, TransferOutService $service)
    {
        //
        return $service->index($request)->find($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
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
