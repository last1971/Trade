<?php

namespace App\Jobs;

use App\Seller;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class ProcessDanPrice implements ShouldQueue, ShouldBeUnique
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
        try {
            $dan = file_get_contents("http://danomsk.ru/upload/dan_dealer.zip");
            Storage::disk('local')->put('dan.zip', $dan);
            $zipper = new ZipArchive();
            $path = Storage::disk('local')->path('dan.zip');
            $zipper->open($path);
            $zipper->setPassword('dandealer000');
            $zipper->extractTo(storage_path('app'));
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
                    $length = mb_strlen($cells[$i]['J']) <= 400 ? strlen($cells[$i]['J']) : 400;
                    $good->fill([
                        'name' => $cells[$i]['C'] . ' ' . $cells[$i]['D'],
                        'remark' => mb_substr($cells[$i]['J'], 0, $length),
                        'basic_delivery_time' => 5,
                        'case' => $cells[$i]['E'],
                        'producer' => $cells[$i]['F'],
                        'is_active' => true,
                        'updated_at' => Carbon::now(),
                    ]);
                    $good->save([ 'timestamps' => false ]);

                    $warehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $good->id]);
                    $warehouse->quantity = $cells[$i]['I'];
                    $warehouse->multiplicity = 1;
                    $warehouse->save();

                    $price = SellerPrice::query()
                        ->firstOrNew(['seller_warehouse_id' => $warehouse->id, 'is_input' => true ]);
                    $price->value = $cells[$i]['H'];
                    $price->save();

                    $price = SellerPrice::query()
                        ->firstOrNew(['seller_warehouse_id' => $warehouse->id, 'is_input' => false ]);
                    $price->value = $cells[$i]['K'];
                    $price->save();
                }
            }
            SellerGood::query()
                ->where('seller_id', $dan->{$dan->getKeyName()})
                ->where('updated_at', '<', $start)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            Log::info('Dan price was imported');
            $this->release();
        } catch (Exception $e) {
            Log::error('Dan price was errored');
            Log::error($e->getMessage());
            Mail::raw($e->getMessage(),function($message){
                $message->to(env('MAIL_DEV','elcopro@gmail.com'))->subject('Error in import DAN price');
            });
            $this->fail($e);
        }
    }
}
