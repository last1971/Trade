<?php

namespace App\Console\Commands;

use App\Good;
use App\GoodName;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
                    'NAME' => GoodName::normalize($good->name->NAME)
                ]);
            }
            $counter++;
        });
        DB::connection('firebird')->statement(
            'INSERT INTO GOOD_NAMES (ID, GOODSCODE, NAME)
            SELECT GEN_ID(GEN_GOODS_SEARCH_ID, 1), g.GOODSCODE, CAST(g.GOODSCODE AS VARCHAR(70))
            FROM GOODS g
            WHERE NOT EXISTS (
                SELECT 1 FROM GOOD_NAMES gn
                WHERE gn.GOODSCODE = g.GOODSCODE AND gn.NAME = CAST(g.GOODSCODE AS VARCHAR(70))
            )'
        );
        return 0;
    }
}
