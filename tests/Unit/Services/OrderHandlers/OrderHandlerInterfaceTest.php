<?php

namespace Tests\Unit\Services\OrderHandlers;

use App\Services\OrderHandlers\OrderHandlerInterface;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Interfaces\APIClient;
use PHPUnit\Framework\TestCase;

class OrderHandlerInterfaceTest extends TestCase
{
    public function testInterfaceHasHandleMethod()
    {
        $reflection = new \ReflectionClass(OrderHandlerInterface::class);
        $this->assertTrue($reflection->hasMethod('handle'));
        
        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isPublic());
        
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);
        
        $this->assertEquals('order', $parameters[0]->getName());
        $this->assertEquals('apiClient', $parameters[1]->getName());
        $this->assertEquals('userId', $parameters[2]->getName());
        
        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    public function testInterfaceHasNoOtherMethods()
    {
        $reflection = new \ReflectionClass(OrderHandlerInterface::class);
        $methods = $reflection->getMethods();
        $this->assertCount(1, $methods);
        $this->assertEquals('handle', $methods[0]->getName());
    }

    public function testInterfaceIsInterface()
    {
        $reflection = new \ReflectionClass(OrderHandlerInterface::class);
        $this->assertTrue($reflection->isInterface());
    }

    public function testInterfaceHasNoProperties()
    {
        $reflection = new \ReflectionClass(OrderHandlerInterface::class);
        $properties = $reflection->getProperties();
        $this->assertCount(0, $properties);
    }
} 