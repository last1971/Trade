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
        'firm-history',
        'good',
        'invoice',
        'invoice-line',
        'name',
        'order',
        'order-line',
        'order-step',
        'payment',
        'payment-order',
        'reserve',
        'retail-price',
        'retail-order-line',
        'retail-sale',
        'retail-sale-line',
        'retail-store-return',
        'seller',
        'seller-good',
        'seller-order',
        'seller-order-line',
        'seller-price',
        'store-line',
        'transfer-out',
        'transfer-out-line',
        'unit-code',
        'unit-code-alias',
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
            Permission::query()->firstOrCreate(['name' => $model . '.*', 'guard_name' => 'api']);
            Permission::query()->firstOrCreate(['name' => $model . '.index', 'guard_name' => 'api']);
            Permission::query()->firstOrCreate(['name' => $model . '.show', 'guard_name' => 'api']);
            Permission::query()->firstOrCreate(['name' => $model . '.store', 'guard_name' => 'api']);
            Permission::query()->firstOrCreate(['name' => $model . '.update', 'guard_name' => 'api']);
            Permission::query()->firstOrCreate(['name' => $model . '.destroy', 'guard_name' => 'api']);
            Permission::query()->firstOrCreate(['name' => $model . '.full', 'guard_name' => 'api']);
            Permission::query()->firstOrCreate(['name' => $model . '.xlsx', 'guard_name' => 'api']);
            Permission::query()->firstOrCreate(['name' => $model . '.pdf', 'guard_name' => 'api']);
        }

        Permission::query()->firstOrCreate(['name' => 'exchange-rate.index', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'goods-list.show', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'goods-list.store', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'goods-list.*', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'invoice.employee', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'invoice.receipt', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'role.index', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'sbis.*', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'sbis.show', 'guard_name' => 'api']);

        Permission::query()->firstOrCreate(['name' => 'nav.*', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.advanced-buyer', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.home', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.goods', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.goods-list', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.invoices', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.invoice-lines', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.orders', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.payments', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.retail-order-lines', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.retail-sale', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.retail-sale-line', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.sbis', 'guard_name' => 'api']);
        Permission::query()->firstOrCreate(['name' => 'nav.transfer-outs', 'guard_name' => 'api']);

        Role::query()->firstOrCreate(['name' => 'guest', 'guard_name' => 'api']);

        $admin = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->syncPermissions([
            'nav.*',
            'advanced-buyer.*',
            'buyer.*',
            'category.*',
            'employee.*',
            'exchange-rate.index',
            'firm.*',
            'firm-history.*',
            'good.*',
            'goods-list.*',
            'invoice.*',
            'invoice-line.*',
            'name.*',
            'order.*',
            'order-line.*',
            'order-step.*',
            'payment.*',
            'payment-order.*',
            'reserve.*',
            'retail-price.*',
            'retail-order-line.*',
            'retail-sale.*',
            'retail-sale-line.*',
            'retail-store-return.*',
            'role.index',
            'sbis.*',
            'seller.*',
            'seller-good.*',
            'seller-price.*',
            'seller-order.*',
            'seller-order-line.*',
            'store-line.*',
            'transfer-out.*',
            'transfer-out-line.*',
            'unit-code.*',
            'unit-code-alias.*',
            'user.*'
        ]);

        $retailer = Role::query()->firstOrCreate(['name' => 'retailer', 'guard_name' => 'api']);
        $retailer->syncPermissions([
            'nav.home',
            'nav.goods',
            'nav.goods-list',
            'nav.retail-order-lines',
            'nav.retail-sale',
            'buyer.*',
            'category.index',
            'employee.index',
            'exchange-rate.index',
            'firm.index',
            'good.index',
            'goods-list.show',
            'goods-list.store',
            'name.index',
            'retail-price.*',
            'retail-order-line.*',
            'retail-sale.*',
            'retail-sale-line.*',
            'retail-store-return.*',
        ]);

        $manager = Role::query()->firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $manager->syncPermissions([
            'nav.advanced-buyer',
            'nav.home',
            'nav.goods',
            'nav.goods-list',
            'nav.invoices',
            'nav.invoice-lines',
            'nav.orders',
            'nav.sbis',
            'nav.transfer-outs',
            'advanced-buyer.*',
            'buyer.*',
            'category.*',
            'employee.index',
            'employee.show',
            'exchange-rate.index',
            'firm.index',
            'firm.show',
            'firm-history.index',
            'firm-history.show',
            'good.*',
            'invoice.*',
            'invoice-line.*',
            'name.*',
            'order.*',
            'order-line.*',
            'reserve.*',
            'sbis.*',
            'seller.*',
            'seller-good.*',
            'seller-price.*',
            'store-line.*',
            'transfer-out.*',
            'transfer-out-line.*',
        ]);

        $buyer = Role::query()->firstOrCreate(['name' => 'buyer', 'guard_name' => 'api']);
        $buyer->syncPermissions([
            'nav.home',
            'nav.invoices',
            'nav.invoice-lines',
            'nav.transfer-outs',
            'buyer.index',
            'buyer.show',
            'category.index',
            'category.show',
            'exchange-rate.index',
            'firm.index',
            'firm.show',
            'firm-history.index',
            'firm-history.show',
            'good.show',
            'invoice.destroy',
            'invoice.index',
            'invoice.show',
            'invoice.pdf',
            'invoice-line.index',
            'invoice-line.show',
            'invoice-line.xlsx',
            'name.index',
            'name.show',
            'order-line.index',
            'transfer-out.index',
            'transfer-out.show',
            'transfer-out-line.index',
            'transfer-out-line.show',
            'transfer-out-line.xlsx',
        ]);

        $buh = Role::query()->firstOrCreate(['name' => 'buh', 'guard_name' => 'api']);
        $buh->syncPermissions([
            'nav.payments',
            'nav.invoices',
            'nav.invoice-lines',
            'nav.transfer-outs',
            'buyer.*',
            'category.index',
            'exchange-rate.index',
            'firm.*',
            'firm-history.index',
            'firm-history.show',
            'invoice.*',
            'invoice-line.*',
            'name.*',
            'order.*',
            'order-line.*',
            'payment.*',
            'payment-order.*',
            'seller.*',
            'transfer-out.*',
            'transfer-out-line.*',
        ]);
    }
}
