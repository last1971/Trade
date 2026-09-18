<?php


namespace App\Http\Controllers\Api;


use App\Http\Requests\IndexRequest;
use App\Services\StoreInService;

class StoreInController extends ModelController
{
    public function __construct()
    {
        parent::__construct(StoreInService::class);
    }

    public function index(IndexRequest $request)
    {
        // В магазине строки прихода лежат в SHOPIN, а номер и дата накладной — в
        // приходном документе SHOPINPR (на складе оба набора в одной SKLADIN).
        $this->service->setRawFrom(
            config('app.is_shop')
                ? '(SELECT s.NP, MIN(s.DATA) AS DATA, MIN(r.NDOC) AS NDOC, MIN(r.DATA_DOC) AS DATA_DOC,
                COUNT(*) AS LINES_COUNT, SUM(s.QUAN) AS QUAN, SUM(p.PRICE * p.QUAN) AS SUMMAP,
                MIN(p.WHEREISPOSTCODE) AS WHEREISPOSTCODE
              FROM SHOPIN s LEFT JOIN PR_META p ON p.SHOPINCODE = s.SHOPINCODE
                            LEFT JOIN SHOPINPR r ON r.SHOPINPRCODE = s.SHOPINPRCODE
              GROUP BY s.NP) as "store_ins"'
                : '(SELECT s.NP, MIN(s.DATA) AS DATA, MIN(s.NDOC) AS NDOC, MIN(s.DATA_DOC) AS DATA_DOC,
                COUNT(*) AS LINES_COUNT, SUM(s.QUAN) AS QUAN, SUM(p.PRICE * p.QUAN) AS SUMMAP,
                MIN(p.WHEREISPOSTCODE) AS WHEREISPOSTCODE
              FROM SKLADIN s LEFT JOIN PR_META p ON p.SKLADINCODE = s.SKLADINCODE
              GROUP BY s.NP) as "store_ins"',
            []
        );
        // store_ins.* вместо *: Firebird не принимает «*, (подзапрос)» при агрегатах
        $request->merge(['selectAttributes' => 'store_ins.*']);
        return parent::index($request);
    }
}
