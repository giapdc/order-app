<?php

namespace Tests\Unit\Services;

use App\Services\OrderProcessingService;
use App\Interfaces\DatabaseService;
use App\Interfaces\APIClient;
use App\Services\OrderHandlers\OrderHandlerFactory;
use App\Services\OrderHandlers\TypeAHandler;
use App\Services\OrderHandlers\TypeBHandler;
use App\Services\OrderHandlers\TypeCHandler;
use App\Services\OrderHandlers\DefaultHandler;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\OrderPriority;
use App\Enums\OrderThreshold;
use App\Exceptions\DatabaseException;
use App\Exceptions\APIException;
use App\Responses\APIResponse;
use Mockery;
use PHPUnit\Framework\TestCase;

class OrderProcessingServiceTest extends TestCase
{
    private $dbService;
    private $apiClient;
    private $orderHandlerFactory;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbService = Mockery::mock(DatabaseService::class);
        $this->apiClient = Mockery::mock(APIClient::class);
        $this->orderHandlerFactory = Mockery::mock(OrderHandlerFactory::class);
        $this->service = new OrderProcessingService($this->dbService, $this->apiClient, $this->orderHandlerFactory);
    }

    public function test_process_orders_successfully_with_type_a(): void
    {
        $userId = 1;
        $order = new Order(1, 'A', 50, false);
        $handler = new TypeAHandler();

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('A')
            ->once()
            ->andReturn($handler);

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order->id, OrderStatus::EXPORTED, OrderPriority::LOW)
            ->once();

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(OrderStatus::EXPORTED, $result[0]->status);
        $this->assertEquals(OrderPriority::LOW, $result[0]->priority);
    }

    public function test_process_orders_with_high_value(): void
    {
        $userId = 1;
        $order = new Order(1, 'A', OrderThreshold::HIGH + 1, false);
        $handler = new TypeAHandler();

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('A')
            ->once()
            ->andReturn($handler);

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order->id, OrderStatus::EXPORTED, OrderPriority::HIGH)
            ->once();

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(OrderStatus::EXPORTED, $result[0]->status);
        $this->assertEquals(OrderPriority::HIGH, $result[0]->priority);
    }

    public function test_process_orders_with_type_b(): void
    {
        $userId = 1;
        $order = new Order(1, 'B', 50, false);
        $handler = new TypeBHandler();
        $apiOrder = new Order(1, 'B', 75, false);
        $apiResponse = new APIResponse('success', $apiOrder);

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('B')
            ->once()
            ->andReturn($handler);

        $this->apiClient->shouldReceive('callAPI')
            ->with($order->id)
            ->once()
            ->andReturn($apiResponse);

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order->id, OrderStatus::PROCESSED, OrderPriority::LOW)
            ->once();

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function test_process_orders_with_type_c(): void
    {
        $userId = 1;
        $order = new Order(1, 'C', 50, true);
        $handler = new TypeCHandler();

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('C')
            ->once()
            ->andReturn($handler);

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order->id, OrderStatus::COMPLETED, OrderPriority::LOW)
            ->once();

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function test_process_orders_with_unknown_type(): void
    {
        $userId = 1;
        $order = new Order(1, 'D', 50, false);
        $handler = new DefaultHandler();

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('D')
            ->once()
            ->andReturn($handler);

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order->id, OrderStatus::UNKNOWN_TYPE, OrderPriority::LOW)
            ->once();

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function test_process_orders_with_database_error(): void
    {
        $userId = 1;
        $order = new Order(1, 'A', 50, false);
        $handler = new TypeAHandler();

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('A')
            ->once()
            ->andReturn($handler);

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order->id, OrderStatus::EXPORTED, OrderPriority::LOW)
            ->once()
            ->andThrow(new DatabaseException('Database error'));

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(OrderStatus::DB_ERROR, $result[0]->status);
    }

    public function test_process_orders_with_multiple_orders(): void
    {
        $userId = 1;
        $order1 = new Order(1, 'A', OrderThreshold::HIGH + 1, false);
        $order2 = new Order(2, 'B', 50, false);
        $handler1 = new TypeAHandler();
        $handler2 = new TypeBHandler();
        $apiOrder = new Order(2, 'B', 75, false);
        $apiResponse = new APIResponse('success', $apiOrder);

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([$order1, $order2]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('A')
            ->once()
            ->andReturn($handler1);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('B')
            ->once()
            ->andReturn($handler2);

        $this->apiClient->shouldReceive('callAPI')
            ->with($order2->id)
            ->once()
            ->andReturn($apiResponse);

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order1->id, OrderStatus::EXPORTED, OrderPriority::HIGH)
            ->once();

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order2->id, OrderStatus::PROCESSED, OrderPriority::LOW)
            ->once();

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_process_orders_with_api_exception(): void
    {
        $userId = 1;
        $order = new Order(1, 'B', 50, false);
        $handler = new TypeBHandler();

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('B')
            ->once()
            ->andReturn($handler);

        $this->apiClient->shouldReceive('callAPI')
            ->with($order->id)
            ->once()
            ->andThrow(new APIException('API error'));

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order->id, OrderStatus::API_FAILURE, OrderPriority::LOW)
            ->once();

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(OrderStatus::API_FAILURE, $result[0]->status);
    }

    public function test_process_orders_with_runtime_exception(): void
    {
        $userId = 1;
        $order = new Order(1, 'type_a', 100, false);
        $order->status = OrderStatus::PENDING;

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('type_a')
            ->andThrow(new \RuntimeException('Test exception'));

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(OrderStatus::ERROR, $result[0]->status);
    }

    public function test_process_orders_with_invalid_argument_exception(): void
    {
        $userId = 0;
        $order = new Order(1, 'type_a', 100, false);
        $order->status = OrderStatus::PENDING;

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('type_a')
            ->andThrow(new \InvalidArgumentException('Invalid user ID'));

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(OrderStatus::ERROR, $result[0]->status);
    }

    public function test_process_orders_with_api_error_status(): void
    {
        $userId = 1;
        $order = new Order(1, 'B', 50, false);
        $handler = new TypeBHandler();
        $apiOrder = new Order(1, 'B', 75, false);
        $apiResponse = new APIResponse('error', $apiOrder);

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('B')
            ->once()
            ->andReturn($handler);

        $this->apiClient->shouldReceive('callAPI')
            ->with($order->id)
            ->once()
            ->andReturn($apiResponse);

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order->id, OrderStatus::API_ERROR, OrderPriority::LOW)
            ->once();

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(OrderStatus::API_ERROR, $result[0]->status);
    }

    public function test_process_orders_with_error_status(): void
    {
        $userId = 1;
        $order = new Order(1, 'B', 150, false);
        $handler = new TypeBHandler();
        $apiOrder = new Order(1, 'B', 75, false);
        $apiResponse = new APIResponse('success', $apiOrder);

        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([$order]);

        $this->orderHandlerFactory->shouldReceive('getHandler')
            ->with('B')
            ->once()
            ->andReturn($handler);

        $this->apiClient->shouldReceive('callAPI')
            ->with($order->id)
            ->once()
            ->andReturn($apiResponse);

        $this->dbService->shouldReceive('updateOrderStatus')
            ->with($order->id, OrderStatus::ERROR, OrderPriority::LOW)
            ->once();

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(OrderStatus::ERROR, $result[0]->status);
    }

    public function test_process_orders_with_empty_orders(): void
    {
        $userId = 1;
        $this->dbService->shouldReceive('getOrdersByUser')
            ->with($userId)
            ->once()
            ->andReturn([]);

        $result = $this->service->processOrders($userId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_order_processing_service_initialization(): void
    {
        $this->assertInstanceOf(OrderProcessingService::class, $this->service);
        $this->assertInstanceOf(DatabaseService::class, $this->dbService);
        $this->assertInstanceOf(APIClient::class, $this->apiClient);
        $this->assertInstanceOf(OrderHandlerFactory::class, $this->orderHandlerFactory);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
