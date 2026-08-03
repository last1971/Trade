<?php


namespace App\Services;


use App\SpisSklad;
use Illuminate\Database\Eloquent\Builder;

class SpisSkladService extends ModelService
{
    protected $dateAttributes = [
        'DATA',
    ];

    public function __construct()
    {
        parent::__construct(SpisSklad::class);

        $this->aliases['name.NAME'] = function (Builder $query) {
            $query
                ->join('GOODS as good', 'good.GOODSCODE', '=', 'SPISSKLAD.GOODSCODE')
                ->join('NAME as name', 'name.NAMECODE', '=', 'good.NAMECODE');
        };

        $this->aliases['reason.NAME'] = function (Builder $query) {
            $query->join('OSN_SPIS as reason', 'reason.ID', '=', 'SPISSKLAD.OSN_SPIS_ID');
        };
    }
}
