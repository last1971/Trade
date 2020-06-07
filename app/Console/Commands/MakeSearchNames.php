<?php

namespace App\Console\Commands;

use App\Good;
use App\GoodName;
use Illuminate\Console\Command;

class MakeSearchNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:searchname';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncronize Goods';

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
     * @return mixed
     */
    public function handle()
    {
        //
        $counter = 1;
        Good::query()->with('name')->chunk(1000, function ($goods) use (&$counter) {
            echo $counter * 1000 . ' . ';
            foreach ($goods as $good) {
                GoodName::query()->firstOrCreate([
                    'GOODSCODE' => $good->GOODSCODE,
                    'NAME' => mb_ereg_replace(config('app.search_replace'), '', $good->name->NAME)
                ]);
            }
            $counter++;
        });
    }
}
