<?php


namespace App\Services;


use App\MarkCode;
use Illuminate\Database\Eloquent\Builder;

class MarkCodeService extends ModelService
{
    protected $dateAttributes = [
        'CREATED_AT', 'RETIRED_AT', 'STATUS_UPDATED_AT',
    ];

    public function __construct()
    {
        parent::__construct(MarkCode::class);

        $this->aliases['name.NAME'] = function (Builder $query) {
            $query
                ->join('GOODS as good', 'good.GOODSCODE', '=', 'MARKCODES.GOODSCODE')
                ->join('NAME as name', 'name.NAMECODE', '=', 'good.NAMECODE');
        };

        // leftJoin: сортировка не должна выкидывать марки без документа,
        // а фильтру с where разница безразлична
        $this->aliases['invoice.NS'] = function (Builder $query) {
            $query
                ->leftJoin('REALPRICE as rp', 'rp.REALPRICECODE', '=', 'MARKCODES.REALPRICECODE')
                ->leftJoin('S as invoice', 'invoice.SCODE', '=', 'rp.SCODE');
        };

        $this->aliases['transferOut.NSF'] = function (Builder $query) {
            $query
                ->leftJoin('REALPRICEF as rpf', 'rpf.REALPRICEFCODE', '=', 'MARKCODES.REALPRICEFCODE')
                ->leftJoin('SF as transferOut', 'transferOut.SFCODE', '=', 'rpf.SFCODE');
        };

        $this->aliases['storeLine.NP'] = function (Builder $query) {
            $query->leftJoin('SKLADIN as storeLine', 'storeLine.SKLADINCODE', '=', 'MARKCODES.SKLADINCODE');
        };

        // отдельный алиас SKLADIN, чтобы не конфликтовать со storeLine.NP при совместном употреблении
        $this->aliases['orderLine.MASTER_ID'] = function (Builder $query) {
            $query
                ->leftJoin('SKLADIN as sl_ord', 'sl_ord.SKLADINCODE', '=', 'MARKCODES.SKLADINCODE')
                ->leftJoin('ZAKAZ_DETAIL as orderLine', 'orderLine.ID', '=', 'sl_ord.ZAKAZ_DETAIL_ID');
        };
    }
}
