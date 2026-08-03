<?php


namespace App\Services;


use App\StoreIn;
use Illuminate\Database\Eloquent\Builder;

class StoreInService extends ModelService
{
    protected $dateAttributes = [
        'DATA', 'DATA_DOC',
    ];

    public function __construct()
    {
        parent::__construct(StoreIn::class);

        $this->aliases['seller.NAMEPOST'] = function (Builder $query) {
            $query->join(
                'WHEREISPOST as seller',
                'seller.WHEREISPOSTCODE', '=', 'store_ins.WHEREISPOSTCODE'
            );
        };
    }
}
