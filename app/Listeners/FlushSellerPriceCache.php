<?php

namespace App\Listeners;

use App\Events\SellerGoodUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class FlushSellerPriceCache implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param SellerGoodUpdated $event
     * @return void
     */
    public function handle(SellerGoodUpdated $event)
    {
        $keys = Cache::get('sellerGoodId=' . $event->sellerGood->id, collect());
        $keys->each(function ($key) {
            Cache::forget($key);
        });
        Cache::forget('sellerGoodId=' . $event->sellerGood->id);
    }
}
