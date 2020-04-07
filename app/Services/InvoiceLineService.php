<?php


namespace App\Services;


use App\InvoiceLine;
use Illuminate\Database\Eloquent\Builder;

class InvoiceLineService extends ModelService
{
    public function __construct()
    {
        parent::__construct(InvoiceLine::class);
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
        $this->aliases['name.NAME'] = function (Builder $query) {
            $query
                ->join('GOODS as good', 'good.GOODSCODE', '=', 'REALPRICE.GOODSCODE')
                ->join('NAME as name', 'name.NAMECODE', '=', 'good.NAMECODE');
        };
    }
}
