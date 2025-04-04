<?php

use PHPUnit\Framework\TestCase;
use app\Services\OrderHandlers\TypeCHandler;
use app\Models\Order;
use app\Enums\OrderStatus;
use app\Interfaces\APIClient;

/**
 * Unit tests for the TypeCHandler class.
 * Verifies the behavior of the handler when processing orders of type 'C'.
 */
class TypeCHandlerTest extends TestCase
{
    /**
     * Test that an order of type 'C' with the flag set to true is marked as 'COMPLETED'.
     */
    public function testHandleFlagTrue()
    {
        $order = new Order(1, 'C', 50, true, OrderStatus::NEW);
        $apiClientMock = Mockery::mock(APIClient::class);

        $handler = new TypeCHandler();
        $handler->handle($order, $apiClientMock, 1);

        $this->assertEquals(OrderStatus::COMPLETED, $order->status);
    }

    /**
     * Test that an order of type 'C' with the flag set to false is marked as 'IN_PROGRESS'.
     */
    public function testHandleFlagFalse()
    {
        $order = new Order(1, 'C', 50, false, OrderStatus::NEW);
        $apiClientMock = Mockery::mock(APIClient::class);

        $handler = new TypeCHandler();
        $handler->handle($order, $apiClientMock, 1);

        $this->assertEquals(OrderStatus::IN_PROGRESS, $order->status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
