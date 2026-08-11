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
        $this->service->setRawFrom(
            '(SELECT s.NP, MIN(s.DATA) AS DATA, MIN(s.NDOC) AS NDOC, MIN(s.DATA_DOC) AS DATA_DOC,
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
