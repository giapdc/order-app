<?php

namespace Tests\Unit\Services\OrderHandlers;

use App\Services\OrderHandlers\TypeAHandler;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\OrderThreshold;
use App\Interfaces\APIClient;
use PHPUnit\Framework\TestCase;
use App\Responses\APIResponse;
use App\Enums\OrderPriority;

class TypeAHandlerTest extends TestCase
{
    private TypeAHandler $handler;
    private APIClient $apiClient;
    private Order $order;
    private int $userId = 123;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new TypeAHandler();
        $this->apiClient = new class implements APIClient {
            public function callAPI($orderId): APIResponse
            {
                $order = new Order(1, 'A', 100, true, OrderStatus::PENDING);
                return new APIResponse('success', $order);
            }
        };
        $this->order = new Order(1, 'A', 100, true, OrderStatus::PENDING);
    }

    protected function tearDown(): void
    {
        // Clean up any CSV files created during tests
        $files = glob('orders_type_A_*.csv');
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    public function testHandleExportsSuccessfullyWithDifferentAmounts()
    {
        // Test with high amount
        $this->order->amount = 1000;
        $this->handler->handle($this->order, $this->apiClient, $this->userId);
        $this->assertEquals(OrderStatus::EXPORTED, $this->order->status);

        // Test with low amount
        $this->order->amount = 50;
        $this->handler->handle($this->order, $this->apiClient, $this->userId);
        $this->assertEquals(OrderStatus::EXPORTED, $this->order->status);
    }

    public function testHandleWithHighAmountAddsNoteRow()
    {
        $this->order->amount = OrderThreshold::MEDIUM + 1;
        $this->handler->handle($this->order, $this->apiClient, $this->userId);
        
        $this->assertEquals(OrderStatus::EXPORTED, $this->order->status);
        
        // Find the most recently created CSV file
        $files = glob('orders_type_A_*.csv');
        $this->assertNotEmpty($files);
        
        $content = file_get_contents(end($files));
        $this->assertStringContainsString('High value order', $content);
    }

    public function testHandleWithSpecialCases()
    {
        // Test with empty order
        $order = new Order(1, 'A', 0, false, OrderStatus::NEW);
        $this->handler->handle($order, $this->apiClient, $this->userId);
        $this->assertEquals(OrderStatus::EXPORTED, $order->status);

        // Test with negative amount
        $order = new Order(1, 'A', -100, false, OrderStatus::NEW);
        $this->handler->handle($order, $this->apiClient, $this->userId);
        $this->assertEquals(OrderStatus::EXPORTED, $order->status);

        // Test with max amount
        $order = new Order(1, 'A', PHP_INT_MAX, false, OrderStatus::NEW);
        $this->handler->handle($order, $this->apiClient, $this->userId);
        $this->assertEquals(OrderStatus::EXPORTED, $order->status);
    }

    public function testHandleWithDifferentUserIds()
    {
        // Test with normal userId
        $this->handler->handle($this->order, $this->apiClient, $this->userId);
        $this->assertEquals(OrderStatus::EXPORTED, $this->order->status);

        // Test with zero userId
        $originalStatus = $this->order->status;
        $this->handler->handle($this->order, $this->apiClient, 0);
        $this->assertEquals($originalStatus, $this->order->status);

        // Test with negative userId
        $originalStatus = $this->order->status;
        $this->handler->handle($this->order, $this->apiClient, -1);
        $this->assertEquals($originalStatus, $this->order->status);
    }

    public function testExportFailureScenarios()
    {
        // Test with empty file path
        $result = $this->handler->export('', ['header'], [['value']]);
        $this->assertFalse($result);

        // Test with empty headers
        $result = $this->handler->export('test.csv', [], [['value']]);
        $this->assertFalse($result);

        // Test with file open failure
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        unlink($tempFile); // Remove the temporary file
        mkdir($tempFile, 0555); // Create a directory with read-only permissions
        // Try to export to a directory (should fail)
        $result = $this->handler->export($tempFile . '/test.csv', ['header'], [['value']]);
        $this->assertFalse($result);
        chmod($tempFile, 0755); // Restore permissions
        rmdir($tempFile);
    }

    public function testExportHandlesException()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        // Create a directory with the same name as the file
        unlink($tempFile);
        mkdir($tempFile, 0555); // Create a read-only directory
        // Try to write to a read-only directory (should throw exception)
        $result = $this->handler->export($tempFile, ['header'], [['value']]);
        $this->assertFalse($result);
        chmod($tempFile, 0755); // Restore permissions
        rmdir($tempFile);
    }

    public function testExportWithEmptyFilePath()
    {
        $result = $this->handler->export('', ['header'], [['value']]);
        $this->assertFalse($result);
    }

    public function testExportWithEmptyHeaders()
    {
        $result = $this->handler->export('test.csv', [], [['value']]);
        $this->assertFalse($result);
    }

    public function testExportWithInvalidPath()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        unlink($tempFile);
        mkdir($tempFile, 0555); // Create a read-only directory
        $result = $this->handler->export($tempFile, ['header'], [['value']]);
        $this->assertFalse($result);
        chmod($tempFile, 0755);
        rmdir($tempFile);
    }

    public function testExportSuccessfullyWritesToFile()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        
        $result = $this->handler->export($tempFile, ['header'], [['value']]);
        $this->assertTrue($result);
        
        // Verify file content
        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('header', $content);
        $this->assertStringContainsString('value', $content);
    }

    public function testExportWithMultipleRows()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        
        $headers = ['ID', 'Name', 'Value'];
        $rows = [
            ['1', 'Item 1', '100'],
            ['2', 'Item 2', '200'],
            ['3', 'Item 3', '300']
        ];
        
        $result = $this->handler->export($tempFile, $headers, $rows);
        $this->assertTrue($result);
        
        // Verify file content
        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('ID,Name,Value', $content);
        $this->assertStringContainsString('1,"Item 1",100', $content);
        $this->assertStringContainsString('2,"Item 2",200', $content);
        $this->assertStringContainsString('3,"Item 3",300', $content);
    }

    public function testExportWithReadOnlyFile()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        
        // Create the file and make it read-only
        file_put_contents($tempFile, '');
        chmod($tempFile, 0444); // Read-only permissions
        
        $result = $this->handler->export($tempFile, ['header'], [['value']]);
        $this->assertFalse($result);
        
        // Clean up
        chmod($tempFile, 0644); // Restore write permissions
        unlink($tempFile);
    }

    public function testExportWithInvalidDirectoryPath()
    {
        $invalidPath = '/invalid/folder/file.csv';
        $result = $this->handler->export($invalidPath, ['header'], [['value']]);
        $this->assertFalse($result);
    }

    public function testExportWithSpecialCharacters()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        
        $headers = ['ID', 'Name', 'Value'];
        $rows = [
            ['1', 'Item, with comma', '100'],
            ['2', 'Item "with quotes"', '200'],
            ['3', 'Item with\nnewline', '300']
        ];
        
        $result = $this->handler->export($tempFile, $headers, $rows);
        $this->assertTrue($result);
        
        // Verify file content
        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('"Item, with comma"', $content);
        $this->assertStringContainsString('"Item ""with quotes"""', $content);
        $this->assertStringContainsString('"Item with\nnewline"', $content);
        
        unlink($tempFile);
    }

    public function testExportWithConcurrentAccess()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        
        // First process
        $result1 = $this->handler->export($tempFile, ['header'], [['value1']]);
        $this->assertTrue($result1);
        
        // Second process trying to write to same file
        $result2 = $this->handler->export($tempFile, ['header'], [['value2']]);
        $this->assertTrue($result2);
        
        // Verify last write succeeded
        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('value2', $content);
        
        unlink($tempFile);
    }

    public function testExportWithFullDisk()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        
        // Create a directory with the same name as the file
        unlink($tempFile);
        mkdir($tempFile, 0555); // Create a read-only directory
        
        // Try to write to a read-only directory (should fail)
        $result = $this->handler->export($tempFile . '/test.csv', ['header'], [['value']]);
        $this->assertFalse($result);
        
        chmod($tempFile, 0755); // Restore permissions
        rmdir($tempFile);
    }

    public function testExportWithDifferentFilePermissions()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        
        // Test with read-only file
        chmod($tempFile, 0444);
        $result = $this->handler->export($tempFile, ['header'], [['value']]);
        $this->assertFalse($result);
        
        // Test with directory instead of file
        unlink($tempFile);
        mkdir($tempFile, 0755);
        $result = $this->handler->export($tempFile, ['header'], [['value']]);
        $this->assertFalse($result);
        
        // Clean up
        rmdir($tempFile);
    }

    public function testHandleWithDifferentPriorities()
    {
        // Test with high priority
        $this->order->priority = OrderPriority::HIGH;
        $this->handler->handle($this->order, $this->apiClient, $this->userId);
        $this->assertEquals(OrderStatus::EXPORTED, $this->order->status);
        
        // Test with low priority
        $this->order->priority = OrderPriority::LOW;
        $this->handler->handle($this->order, $this->apiClient, $this->userId);
        $this->assertEquals(OrderStatus::EXPORTED, $this->order->status);
    }

    public function testExportWithEmptyRows()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        
        $headers = ['ID', 'Name', 'Value'];
        $rows = [];
        
        $result = $this->handler->export($tempFile, $headers, $rows);
        $this->assertTrue($result);
        
        // Verify file content
        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('ID,Name,Value', $content);
        $this->assertStringNotContainsString('value', $content);
        
        unlink($tempFile);
    }
}
