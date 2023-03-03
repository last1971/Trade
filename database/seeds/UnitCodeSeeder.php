<?php

namespace Database\Seeders;

use App\UnitCode;
use App\UnitCodeAlias;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $aiases = config('unit_codes');
        foreach (config('unit') as $key => $value) {
            $unitCode = UnitCode::query()->firstOrCreate(['code' => $key, 'name' => $value]);
            foreach (\Arr::where($aiases, fn($value) => strval($key) === $value) as $name => $notUsed) {
                UnitCodeAlias::query()->firstOrCreate(['unit_code_id' => $unitCode->id, 'name' => $name]);
            }
        }
    }
}
