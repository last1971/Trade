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

        // В магазинной базе приходы читаются из SHOPIN, где даты документа нет:
        // фильтр по ней прилететь не должен, но и молча ронять запрос незачем.
        if (config('app.is_shop')) {
            $this->dateAttributes = ['DATA'];
        }

        $this->aggregateAttributes = [
            'markGoodLinesCount' => ['storeLines' => function (Builder $query) {
                $query->markGoodLinesCount();
            }],
        ];

        $this->aliases['seller.NAMEPOST'] = function (Builder $query) {
            $query->join(
                'WHEREISPOST as seller',
                'seller.WHEREISPOSTCODE', '=', 'store_ins.WHEREISPOSTCODE'
            );
        };
    }
}
