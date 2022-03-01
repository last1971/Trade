<?php

namespace App\Jobs;

use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessSeaTronicPrice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 18000;

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
        $path = Storage::disk('local')->path('SeaTronic.xlsx');
        if (!file_exists($path)) return;
        $updatedAt = (new Carbon(filemtime($path)))->tz(config('app.timezone'));
        $sellerId = config('pricing.SeaTronic.sellerId');
        $last = SellerGood::query()
            ->where('seller_id', $sellerId)
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();
        if($last && $last->updated_at->diffInMinutes($updatedAt) === 0) return;

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path);
        $cells = $sheet->getActiveSheet()
            ->toArray(null, true, true, true);
        $count = count($cells);
        for ($i = 2; $i < $count; $i++) {
            $good = SellerGood::query()
                ->firstOrNew(['seller_id' => $sellerId, 'code' => $cells[$i]['A']]);
            $remark = $cells[$i]['E'];
            $multiplicity = 1;
            $good->fill([
                'name' => $cells[$i]['A'],
                'remark' => $remark,
                'basic_delivery_time' => config('pricing.SeaTronic.basicDeliveryTime'),
                'case' => null,
                'producer' => $cells[$i]['B'],
                'package_quantity' => $multiplicity,
                'is_active' => true,
                //'updated_at' => $updatedAt->tz(config('app.timezone')),
            ]);
            $good->save([ 'timestamps' => false ]);
            $good->updated_at = $updatedAt;
            $good->save([ 'timestamps' => false ]);

            $warehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $good->id]);
            $warehouse->quantity = $cells[$i]['C'];
            $warehouse->multiplicity = $multiplicity;
            $warehouse->save();
            $warehouse->sellerPrices()->delete();

            $nextQuantity = 0;
            if ($cells[$i]['F'] && $cells[$i]['F'] < 200) {
                $nextQuantity = floor(200 / $cells[$i]['F']);
            }

            $sellerPrice = new SellerPrice();
            $sellerPrice->fill([
                'seller_warehouse_id' => $warehouse->id,
                'min_quantity' => $multiplicity,
                'max_quantity' => $nextQuantity,
                'value' => floatval($cells[$i]['G']),
                'CharCode' => 'USD',
                'is_input' => true,
            ]);
            $sellerPrice->save();

            if ($nextQuantity) {
                $sellerPrice = new SellerPrice();
                $sellerPrice->fill([
                    'seller_warehouse_id' => $warehouse->id,
                    'min_quantity' => $nextQuantity + 1,
                    'max_quantity' => 0,
                    'value' => floatval($cells[$i]['F']),
                    'CharCode' => 'USD',
                    'is_input' => true,
                ]);
                $sellerPrice->save();
            }
        }
        SellerGood::query()
            ->where('seller_id', $sellerId)
            ->where('updated_at', '<', $updatedAt)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
