<?php

namespace Tests\Unit\Services\OrderHandlers;

use App\Services\OrderHandlers\DefaultHandler;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Interfaces\APIClient;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DefaultHandler class.
 * Verifies the behavior of the handler when processing orders with unknown types.
 */
class DefaultHandlerTest extends TestCase
{
    private DefaultHandler $handler;
    private APIClient $apiClient;
    private Order $order;
    private int $userId = 123;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new DefaultHandler();
        $this->apiClient = new class implements APIClient {
            public function callAPI($orderId): \App\Responses\APIResponse
            {
                return new \App\Responses\APIResponse('success', new Order(1, 'X', 100, true, OrderStatus::PENDING));
            }
        };
        $this->order = new Order(1, 'X', 100, true, OrderStatus::PENDING);
    }

    /**
     * Test that an order with an unknown type is marked as 'UNKNOWN_TYPE'.
     */
    public function testHandleSetsUnknownTypeStatus()
    {
        $this->handler->handle($this->order, $this->apiClient, $this->userId);
        $this->assertEquals(OrderStatus::UNKNOWN_TYPE, $this->order->status);
    }

    public function testHandleWithDifferentOrderTypes()
    {
        $orderTypes = ['D', 'E', 'F', 'G'];
        foreach ($orderTypes as $type) {
            $order = new Order(1, $type, 100, true, OrderStatus::PENDING);
            $this->handler->handle($order, $this->apiClient, $this->userId);
            $this->assertEquals(OrderStatus::UNKNOWN_TYPE, $order->status);
        }
    }

    public function testHandleWithDifferentOrderAmounts()
    {
        $amounts = [0, 50, 100, 1000];
        foreach ($amounts as $amount) {
            $order = new Order(1, 'X', $amount, true, OrderStatus::PENDING);
            $this->handler->handle($order, $this->apiClient, $this->userId);
            $this->assertEquals(OrderStatus::UNKNOWN_TYPE, $order->status);
        }
    }

    public function testHandleWithDifferentFlags()
    {
        $flags = [true, false];
        foreach ($flags as $flag) {
            $order = new Order(1, 'X', 100, $flag, OrderStatus::PENDING);
            $this->handler->handle($order, $this->apiClient, $this->userId);
            $this->assertEquals(OrderStatus::UNKNOWN_TYPE, $order->status);
        }
    }
}
