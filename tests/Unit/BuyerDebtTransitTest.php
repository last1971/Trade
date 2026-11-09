<?php

namespace Tests\Unit;

use App\Good;
use App\Invoice;
use App\InvoiceLine;
use App\Name;
use App\Services\BuyerDebt\BuyerDebtService;
use App\Services\BuyerDebt\TransitAllocator;
use App\Services\Replenish\ReplenishRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BuyerDebtTransitTest extends TestCase
{
    private function demand(int $line, float $qty, array $extra = []): array
    {
        return $extra + ['line' => $line, 'invoice' => $line, 'good' => 549867,
            'date' => '2026-10-01', 'qty' => $qty, 'shipped' => 0, 'picked' => 0, 'reserved' => 0];
    }

    private function invoke(object $service, string $method, ...$args)
    {
        $reflection = new ReflectionMethod($service, $method);
        $reflection->setAccessible(true);
        return $reflection->invoke($service, ...$args);
    }

    public function testSupplierStockIsUsedOnlyOnceAcrossTheFourRealInvoices(): void
    {
        $allocation = (new TransitAllocator())->allocate([
            $this->demand(610125, 250, ['date' => '2026-10-21', 'invoice' => 98253]),
            $this->demand(606244, 100, ['date' => '2026-09-17', 'invoice' => 95672]),
            $this->demand(608443, 200, ['date' => '2026-10-06', 'invoice' => 97213]),
            $this->demand(606928, 55, ['date' => '2026-09-22', 'invoice' => 96095]),
        ], [549867 => 460]);

        $this->assertSame([606244 => 100.0, 606928 => 55.0, 608443 => 200.0, 610125 => 105.0], $allocation);
        $this->assertSame(460.0, array_sum($allocation));
        $this->assertSame(145.0, 250 - $allocation[610125]);
    }

    public function testPickedReservedAndAlreadyShippedUnitsDoNotConsumeSupplyAgain(): void
    {
        $allocation = (new TransitAllocator())->allocate([
            $this->demand(1, 50, ['shipped' => 50, 'picked' => 50]),
            $this->demand(2, 20, ['shipped' => 5, 'picked' => 12, 'reserved' => 3]),
            $this->demand(3, 10),
        ], [549867 => 12]);

        $this->assertSame([1 => 0.0, 2 => 5.0, 3 => 7.0], $allocation);
    }

    public function testStableInvoiceAndLinePriorityAndIndependentGoods(): void
    {
        $allocation = (new TransitAllocator())->allocate([
            $this->demand(30, 10, ['invoice' => 20]),
            $this->demand(20, 10, ['invoice' => 10]),
            $this->demand(10, 10, ['invoice' => 10]),
            $this->demand(40, 3, ['good' => 123]),
            $this->demand(50, 3, ['good' => 456]),
        ], [549867 => 15, 123 => 2]);

        $this->assertSame(10.0, $allocation[10]);
        $this->assertSame(5.0, $allocation[20]);
        $this->assertSame(0.0, $allocation[30]);
        $this->assertSame(2.0, $allocation[40]);
        $this->assertSame(0.0, $allocation[50]);
    }

    public function testShortageFlowsIntoTheReportAndMoneyStillAddsUp(): void
    {
        $line = new InvoiceLine();
        $line->setRawAttributes(['REALPRICECODE' => 610125, 'GOODSCODE' => 549867,
            'QUAN' => 250, 'PRICE' => 550.84, 'SUMMAP' => 137710.0]);
        foreach (['transferOutLines', 'pickUps', 'reserves'] as $relation) {
            $line->setRelation($relation, collect());
        }
        $name = new Name();
        $name->setRawAttributes(['NAME' => 'TD25830']);
        $line->setRelation('name', $name);
        $line->setRelation('good', (new Good())->setRawAttributes(['BODY' => '', 'PRODUCER' => '']));
        $invoice = (new Invoice())->setRawAttributes(['SCODE' => 98253, 'NS' => 21289,
            'DATA' => '2026-10-21', 'STATUS' => 3]);
        $invoice->setRelation('invoiceLines', collect([$line]));
        foreach (['cashFlows', 'deposits', 'transferOuts'] as $relation) {
            $invoice->setRelation($relation, collect());
        }
        $service = new BuyerDebtService(new ReplenishRepository(), new TransitAllocator());

        $report = $this->invoke($service, 'buildInvoice', $invoice, [610125 => 105.0]);

        $this->assertSame(145.0, $report['holes'][0]['qty']);
        $this->assertSame(549867, $report['holes'][0]['GOODSCODE']);
        $this->assertSame(79871.8, $report['notComing']);
        $this->assertSame(57838.2, $report['coming']);
        $this->assertSame($report['remainingToShip'], $report['onHand'] + $report['coming'] + $report['notComing']);
        $this->assertSame(137710.0, $report['debt']);
    }

    public function testOtherBuyersAndEarlierDatesKeepTheirPlaceInTheSharedSupply(): void
    {
        $previous = Facade::getFacadeApplication();
        $container = Container::getInstance();
        $resolvedDb = DB::getFacadeRoot();
        try {
            Facade::setFacadeApplication(new Container());
            DB::swap(Mockery::mock());
            $repo = Mockery::mock(ReplenishRepository::class);
            $repo->shouldReceive('inTransit')->once()->with([549867])->andReturn([549867 => 5.0]);
            $connection = Mockery::mock();
            $query = Mockery::mock();
            DB::shouldReceive('connection')->once()->with('firebird')->andReturn($connection);
            $connection->shouldReceive('table')->once()->with('REALPRICE as R')->andReturn($query);
            $query->shouldReceive('join')->once()->with('S as INV', 'INV.SCODE', '=', 'R.SCODE')->andReturnSelf();
            $query->shouldReceive('whereIn')->once()->with('INV.STATUS', [2, 3, 4])->andReturnSelf();
            $query->shouldReceive('whereIn')->once()->with('R.GOODSCODE', [549867])->andReturnSelf();
            $query->shouldNotReceive('where'); // Нет ограничения текущим покупателем или датой страницы.
            DB::shouldReceive('raw')->times(3)->andReturnUsing(fn($sql) => $sql);
            $query->shouldReceive('get')->once()->andReturn(collect([
                (object)$this->demand(1, 4, ['date' => '2025-12-01', 'buyer' => 999]),
                (object)$this->demand(2, 4, ['date' => '2026-10-01', 'buyer' => 5269]),
            ]));
            $line = (new InvoiceLine())->setRawAttributes(['GOODSCODE' => 549867]);
            $invoice = (new Invoice())->setRelation('invoiceLines', collect([$line]));
            $service = new BuyerDebtService($repo, new TransitAllocator());

            $allocation = $this->invoke($service, 'allocateTransit', collect([$invoice]));

            $this->assertSame([1 => 4.0, 2 => 1.0], $allocation);
        } finally {
            Mockery::close();
            Facade::clearResolvedInstance('db');
            Facade::setFacadeApplication($previous);
            Container::setInstance($container);
            if ($resolvedDb) {
                DB::swap($resolvedDb);
            }
        }
    }

    public function testPartlyReceivedSupplierOrdersProvideOnlyTheUnreceivedRemainder(): void
    {
        $previous = Facade::getFacadeApplication();
        $resolvedDb = DB::getFacadeRoot();
        try {
            Facade::setFacadeApplication(new Container());
            DB::swap(Mockery::mock());
            $connection = Mockery::mock();
            $query = Mockery::mock();
            DB::shouldReceive('connection')->once()->with('firebird')->andReturn($connection);
            $connection->shouldReceive('table')->once()->with('ZAKAZ_DETAIL')->andReturn($query);
            $query->shouldReceive('join')->once()->andReturnSelf();
            $query->shouldReceive('whereIn')->twice()->andReturnSelf();
            DB::shouldReceive('raw')->twice()->andReturnUsing(fn($sql) => $sql);
            $query->shouldReceive('get')->once()->andReturn(collect([
                (object)['g' => 549867, 'q' => 10, 'skl' => 3, 'shp' => 2],
                (object)['g' => 549867, 'q' => 10, 'skl' => 12, 'shp' => 0],
                (object)['g' => 123, 'q' => 4, 'skl' => 4, 'shp' => 0],
            ]));

            $supply = (new ReplenishRepository())->inTransit([549867, 123]);
            $allocation = (new TransitAllocator())->allocate([$this->demand(1, 10)], $supply);

            $this->assertSame([549867 => 5.0, 123 => 0.0], $supply);
            $this->assertSame([1 => 5.0], $allocation);
        } finally {
            Mockery::close();
            Facade::clearResolvedInstance('db');
            Facade::setFacadeApplication($previous);
            if ($resolvedDb) {
                DB::swap($resolvedDb);
            }
        }
    }
}
