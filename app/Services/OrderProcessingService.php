<?php
namespace App\Services;

use App\Interfaces\DatabaseService;
use App\Interfaces\APIClient;
use App\Services\OrderHandlers\OrderHandlerFactory;
use App\Enums\OrderStatus;
use App\Enums\OrderPriority;
use App\Enums\OrderThreshold;
use App\Exceptions\DatabaseException;

class OrderProcessingService
{
    private $dbService;
    private $apiClient;
    private $orderHandlerFactory;

    /**
     * Constructor for OrderProcessingService.
     *
     * @param DatabaseService $dbService The database service for order management.
     * @param APIClient $apiClient The API client for external interactions.
     * @param OrderHandlerFactory $orderHandlerFactory The factory for creating order handlers.
     */
    public function __construct(DatabaseService $dbService, APIClient $apiClient, OrderHandlerFactory $orderHandlerFactory)
    {
        $this->dbService = $dbService;
        $this->apiClient = $apiClient;
        $this->orderHandlerFactory = $orderHandlerFactory;
    }

    /**
     * Processes orders for a given user.
     *
     * @param int $userId The ID of the user whose orders are to be processed.
     * @return array The processed orders.
     */
    public function processOrders(int $userId): array
    {
        $orders = $this->dbService->getOrdersByUser($userId);

        foreach ($orders as $order) {
            try {
                $handler = $this->orderHandlerFactory->getHandler($order->type);
                $handler->handle($order, $this->apiClient, $userId);

                $order->priority = $order->amount > OrderThreshold::HIGH
                    ? OrderPriority::HIGH
                    : OrderPriority::LOW;

                $this->dbService->updateOrderStatus($order->id, $order->status, $order->priority);
            } catch (DatabaseException $e) {
                $order->status = OrderStatus::DB_ERROR;
            } catch (\Exception $e) {
                $order->status = OrderStatus::ERROR;
            }
        }

        return $orders;
    }

}