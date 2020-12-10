<?php

namespace App\Http\Controllers\Api;

use App\GoodName;
use App\Http\Controllers\Controller;
use App\Imports\CompelFactureImport;
use App\Imports\PromelecFactureImport;
use App\Imports\XlsFactureImport;
use App\Order;
use App\Services\GoodService;
use App\Services\OrderLineService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class OrderImportLineController extends Controller
{
    /**
     * @param UploadedFile $file
     * @return mixed
     */
    private function getImport(UploadedFile $file)
    {
        if (strpos($file->getClientOriginalName(), 'facture_') === 0) {
            return new CompelFactureImport();
        } elseif (strpos($file->getClientOriginalName(), 'Архивная отгрузка') === 0){
            return new PromelecFactureImport();
        }
        return new XlsFactureImport();
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
        $rows = Excel::toCollection($import, $file)
            ->get(0)
            ->filter(function ($value) {
                return $value->get('name');
            });
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
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $order = Order::query()->find(intval($id));
        $GOODSCODE = 0;
        $staffId = $request->user()->employee->ID;
        foreach ($request->get('lines') as $line) {
            if ($GOODSCODE === $line['GOODSCODE']) {
                $service = new OrderService();
                $order = $service->create($order->getAttributes());
            }
            $orderLineService = new OrderLineService();
            $orderLineService->create([
                'MASTER_ID' => $order->ID,
                'GOODSCODE' => $line['GOODSCODE'],
                'QUAN' => $line['quantity'],
                'PRICE' => $line['price'],
                'SUMMAP' => $line['amount'],
                'NAME_IN_PRICE' => $line['name'],
                'STRANA' => $line['country'],
                'GTD' => $line['declaration'],
                'STAFF_ID' => $staffId,
            ]);
            GoodName::query()->firstOrCreate([
                'GOODSCODE' => $line['GOODSCODE'],
                'NAME' => mb_ereg_replace(config('app.search_replace'), '', $line['name']),
            ]);
            $GOODSCODE = $line['GOODSCODE'];
        }
        return response()->json(['message' => 'OK']);
    }

}
