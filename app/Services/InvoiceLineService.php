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

        $this->aliases['category.CATEGORY'] = function (Builder $query) {
            $query
                ->join(
                    'CATEGORY as category', 'category.CATEGORYCODE', '=', 'good.CATEGORYCODE'
                );
        };
        $this->aliases['name.NAME'] = function (Builder $query) {
            $query
                ->join('NAME as name', 'name.NAMECODE', '=', 'good.NAMECODE');
        };
    }
}
