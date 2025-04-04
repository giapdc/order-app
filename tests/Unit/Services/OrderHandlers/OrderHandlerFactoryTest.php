<?php

namespace Tests\Unit\Services\OrderHandlers;

use App\Services\OrderHandlers\OrderHandlerFactory;
use App\Services\OrderHandlers\TypeAHandler;
use App\Services\OrderHandlers\TypeBHandler;
use App\Services\OrderHandlers\TypeCHandler;
use App\Services\OrderHandlers\DefaultHandler;
use PHPUnit\Framework\TestCase;

class OrderHandlerFactoryTest extends TestCase
{
    private OrderHandlerFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new OrderHandlerFactory();
    }

    public function testGetHandlerReturnsTypeAHandlerForTypeA()
    {
        $handler = $this->factory->getHandler('A');
        $this->assertInstanceOf(TypeAHandler::class, $handler);
    }

    public function testGetHandlerReturnsTypeBHandlerForTypeB()
    {
        $handler = $this->factory->getHandler('B');
        $this->assertInstanceOf(TypeBHandler::class, $handler);
    }

    public function testGetHandlerReturnsTypeCHandlerForTypeC()
    {
        $handler = $this->factory->getHandler('C');
        $this->assertInstanceOf(TypeCHandler::class, $handler);
    }

    public function testGetHandlerReturnsDefaultHandlerForUnknownType()
    {
        $handler = $this->factory->getHandler('X');
        $this->assertInstanceOf(DefaultHandler::class, $handler);
    }
} 