<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    private $models = [
        'buyer',
        'employee',
        'firm',
        'invoice',
        'invoice-line',
        'order',
        'order-line',
        'seller',
        'transfer-out',
        'transfer-out-line',
        'user'
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        foreach ($this->models as $model) {
            Permission::query()->firstOrCreate(['name' => $model . '.*']);
            Permission::query()->firstOrCreate(['name' => $model . '.index']);
            Permission::query()->firstOrCreate(['name' => $model . '.show']);
            Permission::query()->firstOrCreate(['name' => $model . '.update']);
            Permission::query()->firstOrCreate(['name' => $model . '.full']);
        }

        Permission::query()->firstOrCreate(['name' => 'role.index']);

        Permission::query()->firstOrCreate(['name' => 'nav.*']);
        Permission::query()->firstOrCreate(['name' => 'nav.home']);
        Permission::query()->firstOrCreate(['name' => 'nav.invoices']);
        Permission::query()->firstOrCreate(['name' => 'nav.invoice-lines']);
        Permission::query()->firstOrCreate(['name' => 'nav.orders']);
        Permission::query()->firstOrCreate(['name' => 'nav.transfer-outs']);

        Role::query()->firstOrCreate(['name' => 'guest']);

        $admin = Role::query()->firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'nav.*',
            'buyer.*',
            'employee.*',
            'firm.*',
            'invoice.*',
            'invoice-line.*',
            'order.*',
            'order-line.*',
            'role.index',
            'seller.*',
            'transfer-out.*',
            'transfer-out-line.*',
            'user.*'
        ]);

        $manager = Role::query()->firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'nav.home',
            'nav.invoices',
            'nav.invoice-lines',
            'nav.orders',
            'nav.transfer-outs',
            'buyer.*',
            'employee.index',
            'firm.index',
            'invoice.*',
            'invoice-line.*',
            'order.*',
            'order-line.*',
            'seller.*',
            'transfer-out.*',
            'transfer-out-line.*',
        ]);

        $buyer = Role::query()->firstOrCreate(['name' => 'buyer']);
        $buyer->givePermissionTo([
            'nav.invoices',
            'nav.invoice-lines',
            'nav.transfer-outs',
            'buyer.index',
            'buyer.show',
            'firm.index',
            'firm.show',
            'invoice.index',
            'invoice.show',
            'invoice-line.index',
            'invoice-line.show',
            'order-line.index',
            'transfer-out.index',
            'transfer-out.show',
            'transfer-out-line.index',
            'transfer-out-line.show',
        ]);
    }
}
