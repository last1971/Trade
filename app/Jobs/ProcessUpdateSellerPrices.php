<?php

namespace App\Jobs;

use App\SellerPrice;
use App\SellerWarehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUpdateSellerPrices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Collection $sellerPrices;

    private SellerWarehouse $sellerWarehouse;

    private array $exclude;

    /**
     * Create a new job instance.
     *
     * @param Collection $sellerPrices
     */
    public function __construct(Collection $sellerPrices, SellerWarehouse $sellerWarehouse, array $exclude) //Collection $sellerPrices
    {
        $this->sellerPrices = $sellerPrices;
        $this->sellerWarehouse = $sellerWarehouse;
        $this->exclude = $exclude;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $oldPrices = $this
            ->sellerWarehouse
            ->sellerPrices()
            ->orderBy('min_quantity')
            ->orderBy('is_input', 'DESC')
            ->get();
        $needUpdate = $this->sellerPrices->count() !== $oldPrices->count();
        $i = 0;
        while ($i < $oldPrices->count() && !$needUpdate) {
            $oldPrice = $oldPrices->get($i);
            $sellerPrice = $this->sellerPrices->get($i);
            $min_quantity = $oldPrice->min_quantity !== $sellerPrice->min_quantity;
            $max_quantity = $oldPrice->min_quantity !== $sellerPrice->min_quantity;
            $oldValue = intval($oldPrice->value * 10000);
            $sellerValue = intval($sellerPrice->value * 10000);
            $value = abs($oldValue - $sellerValue) > 10;
            $needUpdate = $min_quantity || $max_quantity || $value;
            /*
            if ($needUpdate) {
                Log::info('NeedUpdate', [
                    'Good' => $this->sellerWarehouse->sellerGood->name,
                    'oldValue' => intval($oldPrice->value * 10000),
                    'oldMin' => $oldPrice->min_quantity,
                    'oldMax' => $oldPrice->max_quantity,
                    'oldInput' => $oldPrice->is_input,
                    'sellerValue' => intval($sellerPrice->value * 10000),
                    'sellerMin' => $sellerPrice->min_quantity,
                    'sellerMax' => $sellerPrice->max_quantity,
                    'sellerInput' => $sellerPrice->is_input,
                    'i' => $i,
                ]);
            }
            */
            $i++;
        }
        if ($needUpdate) {
            /*
            Log::info(
                'Crash',
                [
                    'oldPrices' => $oldPrices->map(fn($oldPrice) => $oldPrice->value)->all(),
                    'sellerPrices' => $this->sellerPrices->map(fn($sellerPrice) => $sellerPrice->value)->all(),
                    'sellerWarehouse' => $this->sellerWarehouse->id,
                ]
            );
            */
            $this->sellerWarehouse->sellerPrices()->delete();
            $this->sellerPrices->each(fn(SellerPrice $price) => $price->save([ 'timestamps' =>  false ]));
            $this->sellerWarehouse->sellerGood->clearSearchingCache($this->exclude);
        }
    }
}
