<?php

namespace App\Services\OrderHandlers;

/**
 * Factory for creating order handlers based on the order type.
 */
class OrderHandlerFactory
{
    /**
     * Returns the appropriate handler for the given order type.
     *
     * @param string $type The type of the order (e.g., 'A', 'B', 'C').
     * @return OrderHandlerInterface The handler for the specified order type.
     */
    public function getHandler(string $type): OrderHandlerInterface
    {
        return match ($type) {
            'A' => new TypeAHandler(),
            'B' => new TypeBHandler(),
            'C' => new TypeCHandler(),
            default => new DefaultHandler(),
        };
    }
}
