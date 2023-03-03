<?php

use Database\Seeders\SellerPriceRulesSeeder;
use Database\Seeders\UnitCodeSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(SellerPriceRulesSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(UnitCodeSeeder::class);
    }
}
