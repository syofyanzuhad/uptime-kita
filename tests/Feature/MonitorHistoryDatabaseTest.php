<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Monitor;
use App\Models\User;
use App\Services\MonitorHistoryDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

class MonitorHistoryDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        // Clean up any test databases
        $service = new MonitorHistoryDatabaseService();
        $monitors = Monitor::all();

        foreach ($monitors as $monitor) {
            $service->deleteMonitorDatabase($monitor->id);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_creates_sqlite_database_when_monitor_is_created()
    {
        $service = new MonitorHistoryDatabaseService();

        // Create a monitor
        $monitor = Monitor::factory()->create([
            'url' => 'https://example.com',
            'is_public' => false,
        ]);

        // Check if database was created
        $this->assertTrue($service->monitorDatabaseExists($monitor->id));

        $databasePath = $service->getMonitorDatabasePath($monitor->id);
        $this->assertTrue(File::exists($databasePath));
    }

    /** @test */
    public function it_can_insert_and_retrieve_history_records()
    {
        $service = new MonitorHistoryDatabaseService();

        // Create a monitor
        $monitor = Monitor::factory()->create([
            'url' => 'https://example.com',
            'is_public' => false,
        ]);

        // Insert a history record
        $historyData = [
            'uptime_status' => 'up',
            'message' => 'Test message',
            'response_time_ms' => 150,
            'certificate_status' => 'valid',
        ];

        $result = $service->insertHistory($monitor->id, $historyData);
        $this->assertTrue($result);

        // Retrieve the history records
        $records = $service->getHistory($monitor->id);
        $this->assertCount(1, $records);

        $record = $records[0];
        $this->assertEquals('up', $record['uptime_status']);
        $this->assertEquals('Test message', $record['message']);
        $this->assertEquals(150, $record['response_time_ms']);
        $this->assertEquals('valid', $record['certificate_status']);
    }

    /** @test */
    public function it_can_get_latest_history_record()
    {
        $service = new MonitorHistoryDatabaseService();

        // Create a monitor
        $monitor = Monitor::factory()->create([
            'url' => 'https://example.com',
            'is_public' => false,
        ]);

        // Insert multiple history records
        $service->insertHistory($monitor->id, [
            'uptime_status' => 'up',
            'message' => 'First record',
        ]);

        sleep(1); // Ensure different timestamps

        $service->insertHistory($monitor->id, [
            'uptime_status' => 'down',
            'message' => 'Latest record',
        ]);

        // Get the latest record
        $latestRecord = $service->getLatestHistory($monitor->id);

        $this->assertNotNull($latestRecord);
        $this->assertEquals('down', $latestRecord['uptime_status']);
        $this->assertEquals('Latest record', $latestRecord['message']);
    }

    /** @test */
    public function it_can_cleanup_old_history_records()
    {
        $service = new MonitorHistoryDatabaseService();

        // Create a monitor
        $monitor = Monitor::factory()->create([
            'url' => 'https://example.com',
            'is_public' => false,
        ]);

        // Insert some history records
        $service->insertHistory($monitor->id, [
            'uptime_status' => 'up',
            'message' => 'Recent record',
        ]);

        // Get initial count
        $initialRecords = $service->getHistory($monitor->id);
        $this->assertCount(1, $initialRecords);

        // Cleanup (should not delete recent records)
        $deletedCount = $service->cleanupOldHistory($monitor->id, 30);
        $this->assertEquals(0, $deletedCount);

        // Verify records still exist
        $remainingRecords = $service->getHistory($monitor->id);
        $this->assertCount(1, $remainingRecords);
    }

    /** @test */
    public function it_deletes_database_when_monitor_is_deleted()
    {
        $service = new MonitorHistoryDatabaseService();

        // Create a monitor
        $monitor = Monitor::factory()->create([
            'url' => 'https://example.com',
            'is_public' => false,
        ]);

        // Verify database exists
        $this->assertTrue($service->monitorDatabaseExists($monitor->id));

        // Delete the monitor
        $monitor->delete();

        // Verify database is deleted
        $this->assertFalse($service->monitorDatabaseExists($monitor->id));
    }

    /** @test */
    public function it_creates_database_directory_if_not_exists()
    {
        $service = new MonitorHistoryDatabaseService();

        // Remove the directory if it exists
        $directory = database_path('monitor-histories');
        if (File::exists($directory)) {
            File::deleteDirectory($directory);
        }

        // Create a monitor (should create directory)
        $monitor = Monitor::factory()->create([
            'url' => 'https://example.com',
            'is_public' => false,
        ]);

        // Verify directory was created
        $this->assertTrue(File::exists($directory));
        $this->assertTrue($service->monitorDatabaseExists($monitor->id));
    }
}
