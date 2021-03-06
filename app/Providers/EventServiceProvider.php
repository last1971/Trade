<?php

namespace App\Providers;

use App\Events\SellerGoodUpdated;
use App\Invoice;
use App\Listeners\FlushSellerPriceCache;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        SellerGoodUpdated::class => [
            FlushSellerPriceCache::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Invoice::creating(function($invoice) {
            $invoice->NS =
                $invoice->NS
                ?? Invoice::query()
                ->where('FIRM_ID', '=', $invoice->FIRM_ID)
                ->where('DATA', '>', Carbon::now()->firstOfYear())
                ->max('NS')
                ?? 1;
            $invoice->STAFF_ID = $invoice->STAFF_ID ?? auth()->user()->employee->ID;
        });
    }
}
