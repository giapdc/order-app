<?php

namespace App\Services\OrderHandlers;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Interfaces\APIClient;
use App\Exceptions\APIException;

/**
 * Handles orders of type 'B'.
 * Interacts with an external API to determine the order status.
 */
class TypeBHandler implements OrderHandlerInterface
{
    /**
     * Handles the processing of an order of type 'B'.
     *
     * @param Order $order The order to process.
     * @param APIClient $apiClient The API client for external interactions.
     * @param int $userId The ID of the user associated with the order.
     */
    public function handle(Order $order, APIClient $apiClient, int $userId): void
    {
        try {
            $apiResponse = $apiClient->callAPI($order->id);

            if ($apiResponse->status === 'success') {
                if ($apiResponse->data->amount >= 50 && $order->amount < 100) {
                    $order->status = OrderStatus::PROCESSED;
                } elseif ($apiResponse->data->amount < 50 || $order->flag) {
                    $order->status = OrderStatus::PENDING;
                } else {
                    $order->status = OrderStatus::ERROR;
                }
            } else {
                $order->status = OrderStatus::API_ERROR;
            }
        } catch (APIException $e) {
            $order->status = OrderStatus::API_FAILURE;
        }
    }
}
