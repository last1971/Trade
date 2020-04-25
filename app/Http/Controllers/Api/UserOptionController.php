<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRequest;
use App\UserOption;
use Error;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
        throw new Error('Method impossible');
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
    public function show($id)
    {
        //
        $option = UserOption::query()->whereOption($id)->whereUserId(request()->user()->id)->first();
        return $option ? $option->value : [];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(IndexRequest $request, $id)
    {
        //
        return UserOption::query()->updateOrCreate(
            ['option' => $id, 'user_id' => request()->user()->id],
            ['value' => $request->all()]
        )->value;
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
