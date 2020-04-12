<?php


namespace App\Services;


use App\TransferOutLine;
use Illuminate\Database\Eloquent\Builder;

class TransferOutLineService extends ModelService
{
    public function __construct()
    {
        parent::__construct(TransferOutLine::class);

        $this->query->join('GOODS as good', 'good.GOODSCODE', '=', 'REALPRICEF.GOODSCODE');

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
