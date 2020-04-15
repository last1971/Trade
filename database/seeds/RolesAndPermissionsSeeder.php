<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Permission::query()->firstOrCreate(['name' => 'Full']);
        Permission::query()->firstOrCreate(['name' => 'user.full']);

        Role::query()->firstOrCreate(['name' => 'guest']);
        $admin = Role::query()->firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo('user.full');
    }
}
