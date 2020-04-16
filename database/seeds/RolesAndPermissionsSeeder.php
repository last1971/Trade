<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    private $models = [
        'buyer',
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
            Permission::query()->firstOrCreate(['name' => $model . '.full']);
            Permission::query()->firstOrCreate(['name' => $model . '.partitial']);
        }

        Permission::query()->firstOrCreate(['name' => 'role.index']);

        Permission::query()->firstOrCreate(['name' => 'nav.*']);
        Permission::query()->firstOrCreate(['name' => 'nav.invoices']);
        Permission::query()->firstOrCreate(['name' => 'nav.orders']);
        Permission::query()->firstOrCreate(['name' => 'nav.transfer-outs']);

        Role::query()->firstOrCreate(['name' => 'guest']);

        $admin = Role::query()->firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'nav.*',
            'buyer.*',
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

        $buyer = Role::query()->firstOrCreate(['name' => 'buyer']);
        $buyer->givePermissionTo([
            'nav.invoices',
            'nav.transfer-outs',
            'buyer.index',
            'buyer.show',
            'buyer.partitial',
            'firm.index',
            'firm.show',
            'firm.partitial',
            'invoice.index',
            'invoice.show',
            'invoice.partitial',
            'invoice-line.index',
            'invoice-line.show',
            'invoice-line.partitial',
            'transfer-out.index',
            'transfer-out.show',
            'transfer-out.partitial',
            'transfer-out-line.index',
            'transfer-out-line.show',
            'transfer-out-line.partitial',
        ]);
    }
}
