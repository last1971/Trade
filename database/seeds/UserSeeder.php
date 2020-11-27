<?php

use Illuminate\Database\Seeder;
use App\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        $admin = User::firstOrCreate(
            ['email' => 'elcopro@gmail.com'],
            [
                'name' => 'Vladimir',
                'password' => bcrypt('123456')
            ]
        );
        $admin->assignRole('admin');

        $retailer = User::firstOrCreate(
            ['email' => 'office@elcopro.ru'],
            [
                'name' => 'Retailer',
                'password' => bcrypt('123456')
            ]
        );
        $retailer->assignRole('retailer');

        $manager = User::firstOrCreate(
            ['email' => 'opp@elcopro.ru'],
            [
                'name' => 'Manager',
                'password' => bcrypt('123456')
            ]
        );
        $manager->assignRole('manager');
    }
}
