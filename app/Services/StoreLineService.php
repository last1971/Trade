<?php


namespace App\Services;


use App\ShopLine;
use App\StoreLine;

class StoreLineService extends ModelService
{
    /**
     * Магазинная инсталляция ведёт приходы в SHOPIN, оптовая — в SKLADIN.
     * Разница только в таблице и имени её ключа, поэтому она заперта здесь,
     * а не размножена вторым контроллером и второй страницей.
     */
    private bool $isShop;

    public function __construct()
    {
        $this->isShop = (bool) config('app.is_shop');
        parent::__construct($this->isShop ? ShopLine::class : StoreLine::class);
    }

    public function index($request)
    {
        $metaKey = $this->isShop ? 'PR_META.SHOPINCODE' : 'PR_META.SKLADINCODE';
        $lineKey = $this->isShop ? 'SHOPIN.SHOPINCODE' : 'SKLADIN.SKLADINCODE';

        return parent::index($request)
            ->when($request->get('leftovers') === 'true', function ($query) use ($metaKey, $lineKey) {
                $query
                    ->withSum('fifos', 'QUAN')
                    ->where('QUAN', '>', function($query) use ($metaKey, $lineKey) {
                        $query = $query->from('FIFO_T')
                            ->join('PR_META', 'PR_META.ID', '=', 'FIFO_T.PR_META_IN_ID')
                            ->whereColumn($metaKey, '=', $lineKey)
                            ->selectRaw('COALESCE(SUM(FIFO_T.QUAN), 0)');
                    });
            });
    }
}
