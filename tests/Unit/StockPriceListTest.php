<?php

namespace Tests\Unit;

use App\Services\Marking\StockClassifService;
use App\Services\Replenish\ReplenishRepository;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\TestCase;

class StockPriceListTest extends TestCase
{
    private $previousContainer;
    private $previousFacadeApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $container = new Container();
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstance('db');
        DB::swap(Mockery::mock());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Facade::clearResolvedInstance('db');
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function stockModes(): array
    {
        return [
            'warehouse' => ['warehouse', 8],
            'retail' => ['shop', 18],
        ];
    }

    /** @dataProvider stockModes */
    public function testPriceExclusionsUseGoodsCodeAndPreserveOtherRows(string $mode, int $available): void
    {
        Container::getInstance()->instance('config', new Repository(['marking' => ['stock_mode' => $mode]]));
        $connection = Mockery::mock();
        DB::shouldReceive('connection')->once()->with('firebird')->andReturn($connection);
        $connection->shouldReceive('select')->once()
            ->with('select goodscode from PRICE_EXPORT_EXCLUDED')
            ->andReturn([(object)['GOODSCODE' => '557888']]);

        $stock = Mockery::mock(ReplenishRepository::class);
        $stock->shouldReceive('stock')->once()->with([111, 222])->andReturn([
            111 => ['warehouse' => 10, 'retail' => 20, 'reserved' => 2],
            222 => ['warehouse' => 5, 'retail' => 5, 'reserved' => 0],
        ]);
        $request = new Request();
        $service = Mockery::mock(StockClassifService::class, [$stock])->makePartial();
        $row = ['NAME' => 'Товар с русским названием', 'OST' => 20, 'VAL' => 200,
            'LAST_IN' => '2026-01-01', 'PRODUCER' => 'LANBOO'];
        $service->shouldReceive('list')->once()->with($request, true)->andReturn(['data' => [
            ['GOODSCODE' => 557888] + $row,
            ['GOODSCODE' => 111] + $row,
            ['GOODSCODE' => 222, 'NAME' => 'G968B(S)', 'OST' => 5, 'VAL' => 100,
                'LAST_IN' => null, 'PRODUCER' => 'Gainta'],
        ]]);

        $result = $service->priceList($request, 1.5);

        $this->assertSame([111, 222], array_column($result, 'code'));
        $this->assertSame('Товар с русским названием', $result[0]['title']);
        $this->assertSame($available, $result[0]['available']);
        $this->assertSame(15.0, $result[0]['price']);
        $this->assertSame('G968B(S)', $result[1]['title']);
        $this->assertSame('18+', $result[1]['dc']);
        $this->assertSame(30.0, $result[1]['price']);
    }
}
