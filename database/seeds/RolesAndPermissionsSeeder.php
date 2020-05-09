<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    private $models = [
        'advanced-buyer',
        'buyer',
        'category',
        'employee',
        'firm',
        'good',
        'invoice',
        'invoice-line',
        'name',
        'order',
        'order-line',
        'order-step',
        'retail-price',
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
            Permission::query()->firstOrCreate(['name' => $model . '.store']);
            Permission::query()->firstOrCreate(['name' => $model . '.update']);
            Permission::query()->firstOrCreate(['name' => $model . '.destroy']);
            Permission::query()->firstOrCreate(['name' => $model . '.full']);
            Permission::query()->firstOrCreate(['name' => $model . '.xlsx']);
            Permission::query()->firstOrCreate(['name' => $model . '.pdf']);
        }

        Permission::query()->firstOrCreate(['name' => 'role.index']);
        Permission::query()->firstOrCreate(['name' => 'sbis.*']);
        Permission::query()->firstOrCreate(['name' => 'sbis.show']);

        Permission::query()->firstOrCreate(['name' => 'nav.*']);
        Permission::query()->firstOrCreate(['name' => 'nav.home']);
        Permission::query()->firstOrCreate(['name' => 'nav.goods']);
        Permission::query()->firstOrCreate(['name' => 'nav.invoices']);
        Permission::query()->firstOrCreate(['name' => 'nav.invoice-lines']);
        Permission::query()->firstOrCreate(['name' => 'nav.orders']);
        Permission::query()->firstOrCreate(['name' => 'nav.transfer-outs']);

        Role::query()->firstOrCreate(['name' => 'guest']);

        $admin = Role::query()->firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'nav.*',
            'advanced-buyer.*',
            'buyer.*',
            'category.*',
            'employee.*',
            'firm.*',
            'good.*',
            'invoice.*',
            'invoice-line.*',
            'name.*',
            'order.*',
            'order-line.*',
            'order-step.*',
            'retail-price.*',
            'role.index',
            'sbis.*',
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
            'category.index',
            'employee.index',
            'firm.index',
            'good.index',
            'invoice.*',
            'invoice-line.*',
            'name.index',
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
            'category.index',
            'firm.index',
            'firm.show',
            'invoice.index',
            'invoice.show',
            'invoice.pdf',
            'invoice-line.index',
            'invoice-line.show',
            'invoice-line.xlsx',
            'name.index',
            'order-line.index',
            'transfer-out.index',
            'transfer-out.show',
            'transfer-out-line.index',
            'transfer-out-line.show',
            'transfer-out-line.xlsx',
        ]);
    }
}
