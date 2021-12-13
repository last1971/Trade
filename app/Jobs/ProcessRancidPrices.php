<?php

namespace App\Jobs;

use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class ProcessRancidPrices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pricesQuery = SellerPrice::query()
            ->whereDate('updated_at', '<', \Carbon\Carbon::now()->subDays(7))
            ->select('seller_warehouse_id');
        $warehousesQuery = SellerWarehouse::query()
            ->whereIn('id', $pricesQuery
            )->select('seller_good_id');
        $goodsQuery = SellerGood::query()
            ->whereIn('id', $warehousesQuery)
            ->where('is_active', true)
            ->select('id');
        $i = 0;
        while ($goodsQuery->count() > 0) {
            $goods = $goodsQuery
                ->take(1000)
                ->get()
                ->map(fn($v) => $v->id)
                ->toArray();
            $i += SellerGood::query()->whereIn('id', $goods)->update(['is_active' => false ]);
        }
        Log::info('Clear rancid prices', ['Quantity' => $i]);
    }
}
