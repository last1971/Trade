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

class ProcessPositronPrice implements ShouldQueue
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
        $path = Storage::disk('local')->path('positron.xlsx');
        if (!file_exists($path)) return;
        $updatedAt = (new Carbon(filemtime($path)))->tz(config('app.timezone'));
        $sellerId = config('pricing.Positron.sellerId');
        $last = SellerGood::query()
            ->where('seller_id', $sellerId)
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();
        if($last && $last->updated_at->diffInMinutes($updatedAt) === 0) return;
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $document = $reader->load($path);
        $sheet = $document->getWorksheetIterator()->current();
        foreach ($sheet->getRowIterator() as $row) {
            if ($row->getRowIndex() < 2) continue;
            $cell = $row->getCellIterator('A', 'J');
            $name = $cell->current()->getValue();
            $cell->next();
            $quantity = intval(preg_replace('~\D~', '', $cell->current()->getValue()));
            $cell->next();
            $cell->next();
            $price = floatval($cell->current()->getValue());
            $cell->next();
            $multiplicity = intval($cell->current()->getValue() ?? 1);
            $cell->next();
            $cell->next();
            $remark = $cell->current()->getValue() ?? '';
            $cell->next();
            $remark .= ' ';
            $remark .= $cell->current()->getValue() ?? '';
            $cell->next();
            $cell->next();
            $producer = $cell->current()->getValue();

            $good = SellerGood::query()
                ->firstOrNew(['seller_id' => $sellerId, 'code' => $name]);
            $good->fill([
                'name' => $name,
                'remark' => $remark,
                'basic_delivery_time' => config('pricing.SeaTronic.basicDeliveryTime'),
                'case' => null,
                'producer' => $producer,
                'package_quantity' => $multiplicity,
                'is_active' => true,
                //'updated_at' => $updatedAt->tz(config('app.timezone')),
            ]);
            $good->save([ 'timestamps' => false ]);
            $good->updated_at = $updatedAt;
            $good->save([ 'timestamps' => false ]);

            $warehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $good->id]);
            $warehouse->quantity = $quantity;
            $warehouse->multiplicity = $multiplicity;
            $warehouse->save();
            $warehouse->sellerPrices()->delete();

            $sellerPrice = SellerPrice::query()
                ->firstOrNew(['seller_warehouse_id' => $warehouse->id, 'is_input' => true ]);
            $sellerPrice->value = $price;
            $sellerPrice->min_quantity = $multiplicity;
            $sellerPrice->save();
        }

        SellerGood::query()
            ->where('seller_id', $sellerId)
            ->where('updated_at', '<', $updatedAt)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
