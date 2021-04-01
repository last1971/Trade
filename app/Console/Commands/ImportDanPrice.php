<?php

namespace App\Console\Commands;

use App\Good;
use App\Seller;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportDanPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:dan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Dan prices';

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
        try {
            dd(SellerGood::query()
                ->create(['seller_id' => 1, 'code' => 1]));
            /*$dan = file_get_contents("http://danomsk.ru/upload/dan_dealer.zip");
            Storage::disk('local')->put('dan.zip', $dan);
            $zipper = new \ZipArchive();
            $path = Storage::disk('local')->path('dan.zip');
            $zipper->open($path);
            $zipper->setPassword('dandealer000');
            $zipper->extractTo(storage_path('app'));*/
            $sheet = IOFactory::load(Storage::disk('local')->path('dan_dealer.xls'));
            $cells = $sheet
                ->getActiveSheet()
                ->toArray(null, true, true, true);
            $dan = Seller::query()->where('INN', '5503012474')->first();
            $start = Carbon::now();
            for ($i = 3; $i < count($cells); $i++) {
                if (!empty($cells[$i]['H'])) {
                    $good = SellerGood::query()
                        ->firstOrNew(['seller_id' => $dan->{$dan->getKeyName()}, 'code' => $cells[$i]['B']]);
                    //if (empty($good)) {
                    //    $good = new SellerGood();
                    //    $good->seller_id = $dan->{$dan->getKeyName()};
                    //    $good->code = $cells[$i]['B'];
                    //}
                    $good->name = $cells[$i]['C'] . ' ' . $cells[$i]['D'];
                    $length = mb_strlen($cells[$i]['J']) <= 400 ? strlen($cells[$i]['J']) : 400;
                    $good->remark = mb_substr($cells[$i]['J'], 0, $length);
                    $good->case = $cells[$i]['E'];
                    $good->producer = $cells[$i]['F'];
                    $good->is_active = true;
                    $good->save();
                    dd($good);
                    $warehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $good->id]);
                    $warehouse->quantity = $cells[$i]['I'];
                    $warehouse->save();

                    $price = SellerPrice::query()->firstOrNew(['seller_warehouse_id' => $warehouse->id]);
                    $price->value = $cells[$i]['H'];
                    $price->save();
                }
            }
            SellerGood::query()
                ->where('seller_id', $dan->{$dan->getKeyName()})
                ->where('updated_at', '<', $start)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            \Log::info('Dan price was imported');
            return 0;
        } catch (\Exception $e) {
            \Log::info('Dan price was errored');
            //Mail::raw($e->getMessage(),function($message){
            //    $message->to('elcopro@gmail.com')->subject('Error in import DAN price');
            //});
        }
    }
}
