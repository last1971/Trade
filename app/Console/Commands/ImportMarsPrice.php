<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMarsPrice;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportMarsPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:mars';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Mars prices';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $url = 'http://www.pkfmars.ru/files/prices/mars_full_price_list.xls';
        $data = file_get_contents($url);
        Storage::disk('local')->put('mrs.xls', $data);
        $path = Storage::disk('local')->path('mrs.xls');
        $sheet = IOFactory::load($path);
        $cells = $sheet->getActiveSheet()
            ->toArray(null, true, true, true);
        $start = Carbon::now();
        $sellerId = config('pricing.Mars.sellerId');
        for ($i = 10; $i < count($cells); $i++) {
            if (mb_strpos($cells[$i]['C'] , 'шт' ) >= 0 && $cells[$i]['C'] != null && $cells[$i]['A'] != null) {
                $coeff = $cells[$i]['C'] === 'шт' ? 1 : 1000;
                $s = str_replace( ',', '.', $cells[$i]['D']);
                dd($cells[$i]['D'], $cells[$i]['E']);
                $pos = strpos($s, '/');
                if ($pos > 0) {
                    $s = substr($s, 0, $pos);
                } else if ($pos === 0) {
                    $s = "1";
                }
                if ($s === " ") $s=1;

                if ($cells[$i]['E'] * $coeff > 0) {
                    $good = SellerGood::query()
                        ->firstOrNew(['seller_id' => $sellerId, 'code' => $cells[$i]['A']]);
                    $good->fill([
                        'name' => $cells[$i]['B'],
                        'basic_delivery_time' => config('pricing.Mars.basicDeliveryTime'),
                        'producer' => $cells[$i]['I'],
                        'package_quantity' => $s * $coeff,
                        'is_active' => true,
                        'updated_at' => Carbon::now(),
                    ]);
                    $good->save(['timestamps' => false]);

                    $warehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $good->id]);
                    $warehouse->quantity = $cells[$i]['E'] * $coeff;
                    $warehouse->multiplicity = $s * $coeff;
                    $warehouse->save();

                    $sellerPrice = SellerPrice::query()->firstOrNew(['seller_warehouse_id' => $warehouse->id]);
                    $sellerPrice->fill([
                        'min_quantity' => $s * $coeff,
                        'max_quantity' => $cells[$i]['E'] * $coeff,
                        'value' => mb_ereg_replace('[^0-9.]', '', $cells[$i]['G']) / $coeff,
                        'CharCode' => 'RUB',
                        'is_input' => true,
                    ]);
                    $sellerPrice->save();
                }
            }
        }
        ProcessMarsPrice::dispatch();
        return 0;
    }
}
