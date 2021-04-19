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

        Permission::query()->firstOrCreate(['name' => 'exchange-rate.index']);
        Permission::query()->firstOrCreate(['name' => 'goods-list.show']);
        Permission::query()->firstOrCreate(['name' => 'goods-list.store']);
        Permission::query()->firstOrCreate(['name' => 'goods-list.*']);
        Permission::query()->firstOrCreate(['name' => 'invoice.employee']);
        Permission::query()->firstOrCreate(['name' => 'invoice.receipt']);
        Permission::query()->firstOrCreate(['name' => 'role.index']);
        Permission::query()->firstOrCreate(['name' => 'sbis.*']);
        Permission::query()->firstOrCreate(['name' => 'sbis.show']);

        Permission::query()->firstOrCreate(['name' => 'nav.*']);
        Permission::query()->firstOrCreate(['name' => 'nav.home']);
        Permission::query()->firstOrCreate(['name' => 'nav.goods']);
        Permission::query()->firstOrCreate(['name' => 'nav.goods-list']);
        Permission::query()->firstOrCreate(['name' => 'nav.invoices']);
        Permission::query()->firstOrCreate(['name' => 'nav.invoice-lines']);
        Permission::query()->firstOrCreate(['name' => 'nav.orders']);
        Permission::query()->firstOrCreate(['name' => 'nav.payments']);
        Permission::query()->firstOrCreate(['name' => 'nav.retail-order-lines']);
        Permission::query()->firstOrCreate(['name' => 'nav.retail-sale']);
        Permission::query()->firstOrCreate(['name' => 'nav.retail-sale-line']);
        Permission::query()->firstOrCreate(['name' => 'nav.sbis']);
        Permission::query()->firstOrCreate(['name' => 'nav.transfer-outs']);

        Role::query()->firstOrCreate(['name' => 'guest']);

        $admin = Role::query()->firstOrCreate(['name' => 'admin']);
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
            'transfer-out.*',
            'transfer-out-line.*',
            'user.*'
        ]);

        $retailer = Role::query()->firstOrCreate(['name' => 'retailer']);
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

        $manager = Role::query()->firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'nav.home',
            'nav.invoices',
            'nav.invoice-lines',
            'nav.orders',
            'nav.sbis',
            'nav.transfer-outs',
            'buyer.*',
            'category.index',
            'employee.index',
            'employee.show',
            'exchange-rate.index',
            'firm.index',
            'firm.show',
            'firm-history.index',
            'firm-history.show',
            'good.index',
            'invoice.index',
            'invoice.pdf',
            'invoice.show',
            'invoice.store',
            'invoice.update',
            'invoice-line.*',
            'name.index',
            'order.*',
            'order-line.*',
            'reserve.*',
            'sbis.*',
            'seller.*',
            'transfer-out.*',
            'transfer-out-line.*',
        ]);

        $buyer = Role::query()->firstOrCreate(['name' => 'buyer']);
        $buyer->syncPermissions([
            'nav.invoices',
            'nav.invoice-lines',
            'nav.transfer-outs',
            'buyer.index',
            'buyer.show',
            'category.index',
            'exchange-rate.index',
            'firm.index',
            'firm.show',
            'firm-history.index',
            'firm-history.show',
            'invoice.destroy',
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

        $buh = Role::query()->firstOrCreate(['name' => 'buh']);
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
