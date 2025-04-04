<?php

namespace App\Services\OrderHandlers;

use App\Models\Order;
use App\Interfaces\APIClient;

/**
 * Interface for order handlers.
 * Defines the contract for handling different types of orders.
 */
interface OrderHandlerInterface
{
    /**
     * Processes an order based on its type.
     *
     * @param Order $order The order to process.
     * @param APIClient $apiClient The API client for external interactions.
     * @param int $userId The ID of the user associated with the order.
     */
    public function handle(Order $order, APIClient $apiClient, int $userId): void;
}
