<?php

namespace Tests\Unit;

use App\Services\SellerPriceService;

use ReflectionClass;
use Tests\TestCase;

class SellerPriceTest extends TestCase
{
    private SellerPriceService $service;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->service = new SellerPriceService();
        parent::__construct($name, $data, $dataName);
    }

    protected static function invokeMethod(&$object, $methodName, array $parameters = array()) {
        $class = new ReflectionClass(get_class($object));
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    public function testIsSeller()
    {
        foreach (array_keys($this->service->aliases) as $key) {
            $this->assertTrue($this->invokeMethod($this->service, 'isSeller', array($key)));
        }
        $this->assertFalse($this->invokeMethod($this->service, 'isSeller', array(1)));
        $this->assertFalse($this->invokeMethod($this->service, 'isSeller', array(10)));
        $this->assertFalse($this->invokeMethod($this->service, 'isSeller', array(100)));
    }

    public function testFromSeller()
    {
        foreach (array_keys($this->service->aliases) as $key) {
            $this->assertIsArray(
                $this->invokeMethod($this->service, $this->service->aliases[$key]['function'], array('MAX232'))
            );
        }
    }

}
