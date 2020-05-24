<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\CompelFactureImport;
use App\Imports\XlsFactureImport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class OrderImportLineController extends Controller
{
    /**
     * @param UploadedFile $file
     * @return CompelFactureImport|XlsFactureImport
     */
    private function getImport(UploadedFile $file)
    {
        if (strpos($file->getClientOriginalName(), 'facture_') === 0) {
            return new CompelFactureImport();
        }
        return new XlsFactureImport();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
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
        $file = $request->file('file');
        $import = $this->getImport($file);
        return Excel::toCollection($import, $file);
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
