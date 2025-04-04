<?php

namespace App\Services\OrderHandlers;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\OrderThreshold;
use App\Interfaces\APIClient;

/**
 * Handles orders of type 'A'.
 * Exports order details to a CSV file and updates the order status.
 */
class TypeAHandler implements OrderHandlerInterface
{
    /**
     * Handles the processing of an order of type 'A'.
     *
     * @param Order $order The order to process.
     * @param APIClient $apiClient The API client for external interactions (not used in this handler).
     * @param int $userId The ID of the user associated with the order.
     */
    public function handle(Order $order, APIClient $apiClient, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $csvFile = $this->getExportPath($userId);

        $headers = ['ID', 'Type', 'Amount', 'Flag', 'Status', 'Priority'];
        $rows = [
            [
                $order->id,
                $order->type,
                $order->amount,
                $order->flag ? 'true' : 'false',
                $order->status,
                $order->priority
            ]
        ];
       
        if ($order->amount > OrderThreshold::MEDIUM) {
            $rows[] = ['', '', '', '', 'Note', 'High value order'];
        }

        $success = $this->export($csvFile, $headers, $rows);
        $order->status = $success ? OrderStatus::EXPORTED : OrderStatus::EXPORT_FAILED;
    }

    /**
     * Gets the export path for the CSV file.
     *
     * @param int $userId The ID of the user associated with the order.
     * @return string The path where the CSV file will be created.
     */
    protected function getExportPath(int $userId): string
    {
        return 'orders_type_A_' . $userId . '_' . time() . '.csv';
    }

    /**
     * Exports data to a CSV file.
     *
     * @param string $filePath The path where the CSV file will be created.
     * @param array $headers The column headers for the CSV file.
     * @param array $rows The data rows to be written to the CSV file.
     * @return bool Returns true if the export was successful, false otherwise.
     */
    public function export(string $filePath, array $headers, array $rows): bool
    {
        if (empty($filePath) || empty($headers)) {
            return false;
        }

        $file = fopen($filePath, 'w');
        if ($file === false) {
            return false;
        }

        // Write headers
        fputcsv($file, $headers, ',', '"', '\\');

        // Write rows
        foreach ($rows as $row) {
            fputcsv($file, $row, ',', '"', '\\');
        }

        fclose($file);

        return true;
    }
}

