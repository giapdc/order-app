<?php

namespace App\Services\OrderHandlers;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Interfaces\APIClient;

/**
 * Handles orders of type 'C'.
 * Updates the order status based on the `flag` property.
 */
class TypeCHandler implements OrderHandlerInterface
{
    /**
     * Handles the processing of an order of type 'C'.
     *
     * @param Order $order The order to process.
     * @param APIClient $apiClient The API client for external interactions (not used in this handler).
     * @param int $userId The ID of the user associated with the order.
     */
    public function handle(Order $order, APIClient $apiClient, int $userId): void
    {
        $order->status = $order->flag ? OrderStatus::COMPLETED : OrderStatus::IN_PROGRESS;
    }
}
