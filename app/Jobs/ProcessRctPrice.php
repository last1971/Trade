<?php

namespace App\Jobs;

use App\ExchangeRate;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessRctPrice implements ShouldQueue, ShouldBeUnique
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
        try {
            Log::info('Start RCT pricing');
            $path = Storage::disk('local')->path('rct.xlsx');

            Storage::delete($path);

            $client = new Client();
            $client->request(
                'GET',
                'https://www.rct.ru/price/all',
                [
                    'sink' => $path,
                    'curl' => [ CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1' ],
                ]
            );

            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($path);
            $cells = $sheet->getActiveSheet()
                ->toArray(null, true, true, true);
            $sellerId = config('pricing.Rct.sellerId');
            $start = Carbon::now();
            $usd = ExchangeRate::query()->where('CharCode', 'USD')->latest()->first();
            $usdBigAmount = 15000 / $usd->value;
            $count = count($cells);
            for ($i = 9; $i < $count; $i++) {
                // Log::info($cells[$i]['N']);
                if ($i % 1000 === 0) {
                    Log::info($i . ' from ' . $count);
                }
                if ($cells[$i]['N']) {
                    // Log::info('Code ' . $cells[$i]['E'] . ' start');
                    $good = SellerGood::query()
                        ->firstOrNew(['seller_id' => $sellerId, 'code' => $cells[$i]['E']]);
                    $remark = $cells[$i]['B'] . ' / ' . $cells[$i]['D'] . ' / ' . $cells[$i]['F'];
                    $length = mb_strlen($remark) <= 400 ? strlen($remark) : 400;
                    $multiplicity = $cells[$i]['J'] ?? 1;
                    $good->fill([
                        'name' => $cells[$i]['C'],
                        'remark' => mb_substr($remark, 0, $length),
                        'basic_delivery_time' => config('pricing.Rct.basicDeliveryTime'),
                        'case' => $cells[$i]['G'],
                        'producer' => $cells[$i]['H'],
                        'package_quantity' => $multiplicity,
                        'is_active' => true,
                        'updated_at' => Carbon::now(),
                    ]);
                    $good->save([ 'timestamps' => false ]);

                    $nextQuantity = $multiplicity > 1 ? $multiplicity - 1 : 1;

                    $warehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $good->id]);
                    $warehouse->quantity = $cells[$i]['N'];
                    $warehouse->multiplicity = $multiplicity;
                    $warehouse->save();
                    $warehouse->sellerPrices()->delete();

                    $sellerPrice = new SellerPrice();
                    $sellerPrice->fill([
                        'seller_warehouse_id' => $warehouse->id,
                        'min_quantity' => $multiplicity,
                        'max_quantity' => $cells[$i]['L'] !== 0 ? $nextQuantity : 0,
                        'value' => $cells[$i]['K'] * 1.02,
                        'CharCode' => 'USD',
                        'is_input' => true,
                    ]);
                    $sellerPrice->save();

                    $nextNextQuantity = 0;

                    if ($cells[$i]['L'] !== 0) {
                        $nextNextQuantity = $cells[$i]['M'] === 0
                            ? 0
                            : (int) ($usdBigAmount / $cells[$i]['M'] * 1.02);
                        $nextNextQuantity = ($nextNextQuantity === 0)
                            ? 0
                            : ($nextNextQuantity % $multiplicity !== 0
                                ? $nextNextQuantity + $multiplicity - ($nextNextQuantity % $multiplicity)
                                : $nextNextQuantity);

                        $sellerPrice = new SellerPrice();
                        $sellerPrice->fill([
                            'seller_warehouse_id' => $warehouse->id,
                            'min_quantity' => $nextQuantity + 1,
                            'max_quantity' => $nextNextQuantity !== 0 ? $nextNextQuantity - 1 : 0,
                            'value' => $cells[$i]['L'] * 1.02,
                            'CharCode' => 'USD',
                            'is_input' => true,
                        ]);
                        $sellerPrice->save();
                    }

                    if ($cells[$i]['M'] !== 0) {
                        $sellerPrice = new SellerPrice();
                        $sellerPrice->fill([
                            'seller_warehouse_id' => $warehouse->id,
                            'min_quantity' => $nextNextQuantity,
                            'max_quantity' => 0,
                            'value' => $cells[$i]['M'] * 1.02,
                            'CharCode' => 'USD',
                            'is_input' => true,
                        ]);
                        $sellerPrice->save();
                    }
                }
            }
            SellerGood::query()
                ->where('seller_id', $sellerId)
                ->where('updated_at', '<', $start)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            Log::info('Finish RCT pricing');
        } catch (Exception $e) {
            Log::error('Rct price was errored');
            Log::error($e->getMessage());
            $this->fail($e);
        } catch (GuzzleException $e) {
            Log::error('Rct price was errored');
            Log::error($e->getMessage());
            $this->fail($e);
        }
    }
}
