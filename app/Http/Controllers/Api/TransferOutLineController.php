<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRequest;
use App\Services\TransferOutLineService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TransferOutLineController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param IndexRequest $request
     * @param TransferOutLineService $service
     * @return LengthAwarePaginator
     */
    public function index(IndexRequest $request, TransferOutLineService $service)
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
     * @param TransferOutLineService $service
     * @return LengthAwarePaginator
     */
    public function show($id, IndexRequest $request, TransferOutLineService $service)
    {
        //
        return $service->index($request)->paginate($request->find($id));
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
