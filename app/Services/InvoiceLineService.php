<?php


namespace App\Services;


use App\InvoiceLine;
use Illuminate\Database\Eloquent\Builder;

class InvoiceLineService extends ModelService
{
    public function __construct()
    {
        parent::__construct(InvoiceLine::class);

        $this->query->join('GOODS as good', 'good.GOODSCODE', '=', 'REALPRICE.GOODSCODE');
        $this->query->join('S as invoice', 'invoice.SCODE', '=', 'REALPRICE.SCODE');
        $this->query->join('CATEGORY as category', 'category.CATEGORYCODE', '=', 'good.CATEGORYCODE');

        $this->aggregateAttributes = [
            'reservesQuantity' => ['reserves' => function (Builder $query) {
                $query->reservesQuantity();
            }],
            'pickUpsQuantity' => ['pickUps' => function (Builder $query) {
                $query->pickUpsQuantity();
            }],
            'transferOutLinesQuantity' => ['transferOutLines' => function (Builder $query) {
                $query->transferOutLinesQuantity();
            }],
        ];
        $this->dateAttributes = ['invoice.DATA'];
        $this->aliases['name.NAME'] = function (Builder $query) {
            $query
                ->join('NAME as name', 'name.NAMECODE', '=', 'good.NAMECODE');
        };
    }

    public function index($request)
    {
        $this->addUserBuyers($request, 'invoice');
        $this->addUserFirms($request, 'invoice');
        return parent::index($request);
    }
}
