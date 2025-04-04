<?php

use PHPUnit\Framework\TestCase;
use app\Services\OrderHandlers\TypeBHandler;
use app\Models\Order;
use app\Enums\OrderStatus;
use app\Interfaces\APIClient;
use app\Responses\APIResponse;
use app\Exceptions\APIException;

/**
 * Unit tests for the TypeBHandler class.
 * Verifies the behavior of the handler when processing orders of type 'B'.
 */
class TypeBHandlerTest extends TestCase
{
    /**
     * Test that an order of type 'B' is processed successfully with a status of 'PROCESSED'.
     */
    public function testHandleApiSuccessProcessed()
    {
        $order = new Order(1, 'B', 80, false, OrderStatus::NEW);
        $apiClientMock = Mockery::mock(APIClient::class);

        $apiResponse = new APIResponse('success', $order);
        $apiClientMock->shouldReceive('callAPI')
            ->with($order->id)
            ->andReturn($apiResponse);

        $handler = new TypeBHandler();
        $handler->handle($order, $apiClientMock, 1);

        $this->assertEquals(OrderStatus::PROCESSED, $order->status);
    }

    /**
     * Test that an order of type 'B' is processed successfully with a status of 'PENDING'.
     */
    public function testHandleApiSuccessPending()
    {
        $order = new Order(1, 'B', 40, true, OrderStatus::NEW);
        $apiClientMock = Mockery::mock(APIClient::class);

        $apiResponse = new APIResponse('success', $order);
        $apiClientMock->shouldReceive('callAPI')
            ->with($order->id)
            ->andReturn($apiResponse);

        $handler = new TypeBHandler();
        $handler->handle($order, $apiClientMock, 1);

        $this->assertEquals(OrderStatus::PENDING, $order->status);
    }

    /**
     * Test that an order of type 'B' encounters an API error.
     */
    public function testHandleApiError()
    {
        $order = new Order(1, 'B', 80, false, OrderStatus::NEW);
        $apiClientMock = Mockery::mock(APIClient::class);

        $apiResponse = new APIResponse('error', $order);
        $apiClientMock->shouldReceive('callAPI')
            ->with($order->id)
            ->andReturn($apiResponse);

        $handler = new TypeBHandler();
        $handler->handle($order, $apiClientMock, 1);

        $this->assertEquals(OrderStatus::API_ERROR, $order->status);
    }

    /**
     * Test that an order of type 'B' encounters an API exception.
     */
    public function testHandleApiException()
    {
        $order = new Order(1, 'B', 80, false, OrderStatus::NEW);
        $apiClientMock = Mockery::mock(APIClient::class);

        $apiClientMock->shouldReceive('callAPI')
            ->with($order->id)
            ->andThrow(new APIException());

        $handler = new TypeBHandler();
        $handler->handle($order, $apiClientMock, 1);

        $this->assertEquals(OrderStatus::API_FAILURE, $order->status);
    }

    /**
     * Test that an order of type 'B' encounters an error due to invalid conditions.
     */
    public function testHandleApiSuccessError()
    {
        $order = new Order(1, 'B', 100, false, OrderStatus::NEW);
        $apiClientMock = Mockery::mock(APIClient::class);
        
        $apiResponse = new APIResponse('success', $order);
        $apiClientMock->shouldReceive('callAPI')
            ->with($order->id)
            ->andReturn($apiResponse);
        
        $handler = new TypeBHandler();
        $handler->handle($order, $apiClientMock, 1);
        
        $this->assertEquals(OrderStatus::ERROR, $order->status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
