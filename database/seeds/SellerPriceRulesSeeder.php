<?php

namespace Database\Seeders;

use App\SellerPriceRule;
use Illuminate\Database\Seeder;

class SellerPriceRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SellerPriceRule::query()->updateOrCreate(
            ['alias' => 'buyer_rule'],
            [ 'description' => 'Цена поставщика или 15% к входной']
        );
        SellerPriceRule::query()->updateOrCreate(
            ['alias' => 'full_rule'],
            [ 'description' => 'Все как есть']
        );
    }
}
