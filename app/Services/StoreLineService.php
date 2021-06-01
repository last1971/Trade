<?php


namespace App\Services;


use App\StoreLine;

class StoreLineService extends ModelService
{
    public function __construct()
    {
        parent::__construct(StoreLine::class);
    }

    public function index($request)
    {
        return parent::index($request)
            ->when($request->get('leftovers') === 'true', function ($query) {
                $query
                    ->withSum('fifos', 'QUAN')
                    ->where('QUAN', '>', function($query) {
                        $query = $query->from('FIFO_T')
                            ->join('PR_META', 'PR_META.ID', '=', 'FIFO_T.PR_META_IN_ID')
                            ->whereColumn('PR_META.SKLADINCODE', '=', 'SKLADIN.SKLADINCODE')
                            ->selectRaw('COALESCE(SUM(FIFO_T.QUAN), 0)');
                    });
            });
    }
}
