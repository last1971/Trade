<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\CompelFactureImport;
use App\Imports\XlsFactureImport;
use App\Services\GoodService;
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
    public function store(Request $request, GoodService $service)
    {
        //
        $file = $request->file('file');
        $import = $this->getImport($file);
        $rows = Excel::toCollection($import, $file)->get(0);
        $names = $rows
            ->pluck('name')
            ->unique()
            ->map(function ($name) {
                return mb_ereg_replace(config('app.search_replace'), '', $name);
            })
            ->all();
        $goods = $service->index(collect([
            'with' => [
                'orderStep',
                'retailStore',
                'warehouse',
                'name',
                'goodNames',
                'retailPrice'
            ],
            'aggregateAttributes' => [
                'reservesQuantity',
                'invoiceLinesQuantityTransit',
                'reservesQuantityTransit',
                'pickUpsTransitQuantity',
                'retailOrderLinesNeedQuantity',
                'orderLinesTransitQuantity',
                'shopLinesTransitQuantity',
                'storeLinesTransitQuantity',
            ],
            'filterAttributes' => ['goodNames.NAME'],
            'filterOperators' => ['IN'],
            'filterValues' => [$names],
        ]))->get();
        $rows->each(function ($row, $key) use ($goods) {
            $good = $goods->first(function ($value) use ($row) {
                return $value->goodNames->where(
                    'NAME',
                    '=',
                    mb_ereg_replace(config('app.search_replace'), '', $row->get('name'))
                )->first();
            });
            $row->put('good', $good);
            $row->put('GOODSCODE', $good ? $good->GOODSCODE : null);
            $row->put('id', $key);
        });
        /* $goodNames = GoodName::with('good.name')
            ->whereIn('NAME', $names)
            ->get();
        $rows->each(function ($value) use ($goodNames) {
            $goodName = $goodNames
                ->where(
                    'NAME',
                    '=',
                    mb_ereg_replace(config('app.search_replace'), '', $value->get('name'))
                )
                ->first();
            $value->put('good', $goodName ? $goodName->good : null);
            $a = 1;
        });*/
        return $rows;
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
