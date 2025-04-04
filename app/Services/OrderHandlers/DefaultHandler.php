<?php

namespace App\Services\OrderHandlers;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Interfaces\APIClient;

/**
 * Default handler for unknown order types.
 * Sets the order status to `UNKNOWN_TYPE`.
 */
class DefaultHandler implements OrderHandlerInterface
{
    /**
     * Handles the processing of an order with an unknown type.
     *
     * @param Order $order The order to process.
     * @param APIClient $apiClient The API client for external interactions (not used in this handler).
     * @param int $userId The ID of the user associated with the order.
     */
    public function handle(Order $order, APIClient $apiClient, int $userId): void
    {
        $order->status = OrderStatus::UNKNOWN_TYPE;
    }
}
