<?php

namespace Tests\Unit;

use App\Services\SellerPriceService;

use ReflectionClass;
use Tests\TestCase;

class SellerPriceTest extends TestCase
{
    private SellerPriceService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new SellerPriceService();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->service);
    }

    public function testInitialize()
    {
        $this->assertEquals($this->service, $this->service);
    }

}
