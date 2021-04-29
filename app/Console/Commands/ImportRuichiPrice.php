<?php

namespace App\Console\Commands;

use App\RuichiGood;
use App\SellerGood;
use Carbon\Carbon;
use Exception;
use ZipArchive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportRuichiPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:ruichi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Ruichi prices';

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
            RuichiGood::with(['ruichiPrices', 'ruichiWharehouses'])->chunk(100, function ($goods) {
                foreach ($goods as $good) {
                    echo $good->tovmark . PHP_EOL;
                    break;
                }
                echo ' -------------- ' . PHP_EOL;
            });

        } catch (Exception $e) {
            dd($e);
        }
        return 0;
    }
}
