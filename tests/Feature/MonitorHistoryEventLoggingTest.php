<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Monitor;
use App\Models\User;
use App\Listeners\SendCustomMonitorNotification;
use App\Services\MonitorHistoryDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckSucceeded;
use Spatie\UptimeMonitor\Helpers\Period;

class MonitorHistoryEventLoggingTest extends TestCase
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
    public function it_logs_history_record_when_uptime_check_failed_event_is_fired()
    {
        $service = new MonitorHistoryDatabaseService();

        // Create a monitor
        $monitor = Monitor::factory()->create([
            'url' => 'https://example1.com',
            'is_public' => false,
            'uptime_status' => 'down',
            'uptime_check_failure_reason' => 'Connection timeout',
        ]);

        // Attach user to monitor using syncWithoutDetaching
        $monitor->users()->syncWithoutDetaching([$this->user->id => ['is_active' => true]]);

        // Ensure database exists
        $service->createMonitorDatabase($monitor->id);

        // Create mock event with Period
        $period = new Period(now()->subMinutes(5), now());
        $event = new UptimeCheckFailed($monitor, $period);

        // Handle the event
        $listener = new SendCustomMonitorNotification();
        $listener->handle($event);

        // Check if history record was created
        $historyRecords = $service->getHistory($monitor->id, 10, 0);

        // There might be multiple records (from model creation + event), so check the latest one
        $this->assertGreaterThanOrEqual(1, count($historyRecords));
        $latestRecord = $historyRecords[0]; // Most recent record
        $this->assertEquals('down', $latestRecord['uptime_status']);
        $this->assertEquals('Connection timeout', $latestRecord['message']);
    }

    /** @test */
    public function it_logs_history_record_when_uptime_check_succeeded_event_is_fired()
    {
        $service = new MonitorHistoryDatabaseService();

        // Create a monitor
        $monitor = Monitor::factory()->create([
            'url' => 'https://example2.com',
            'is_public' => false,
            'uptime_status' => 'up',
        ]);

        // Attach user to monitor using syncWithoutDetaching
        $monitor->users()->syncWithoutDetaching([$this->user->id => ['is_active' => true]]);

        // Ensure database exists
        $service->createMonitorDatabase($monitor->id);

        // Create mock event
        $event = new UptimeCheckSucceeded($monitor);

        // Handle the event
        $listener = new SendCustomMonitorNotification();
        $listener->handle($event);

        // Check if history record was created
        $historyRecords = $service->getHistory($monitor->id, 10, 0);

        $this->assertCount(1, $historyRecords);
        $this->assertEquals('up', $historyRecords[0]['uptime_status']);
        $this->assertEquals('Website is online', $historyRecords[0]['message']);
    }

    /** @test */
    public function it_creates_monitor_database_if_not_exists_when_event_is_handled()
    {
        // Create a monitor
        $monitor = Monitor::factory()->create([
            'url' => 'https://example3.com',
            'is_public' => false,
            'uptime_status' => 'down',
        ]);

        // Attach user to monitor using syncWithoutDetaching
        $monitor->users()->syncWithoutDetaching([$this->user->id => ['is_active' => true]]);

        // Delete the database that was created by the model's created event
        $service = new MonitorHistoryDatabaseService();
        $service->deleteMonitorDatabase($monitor->id);

        // Verify database doesn't exist initially
        $this->assertFalse($service->monitorDatabaseExists($monitor->id));

        // Create mock event with Period
        $period = new Period(now()->subMinutes(5), now());
        $event = new UptimeCheckFailed($monitor, $period);

        // Handle the event
        $listener = new SendCustomMonitorNotification();
        $listener->handle($event);

        // Verify database was created
        $this->assertTrue($service->monitorDatabaseExists($monitor->id));

        // Check if history record was created
        $historyRecords = $service->getHistory($monitor->id, 10, 0);
        $this->assertCount(1, $historyRecords);
    }

    /** @test */
    public function it_logs_multiple_history_records_for_different_events()
    {
        $service = new MonitorHistoryDatabaseService();

        // Create a monitor
        $monitor = Monitor::factory()->create([
            'url' => 'https://example4.com',
            'is_public' => false,
        ]);

        // Attach user to monitor using syncWithoutDetaching
        $monitor->users()->syncWithoutDetaching([$this->user->id => ['is_active' => true]]);

        // Ensure database exists
        $service->createMonitorDatabase($monitor->id);

        $listener = new SendCustomMonitorNotification();

        // Fire failed event with Period
        $period = new Period(now()->subMinutes(5), now());
        $failedEvent = new UptimeCheckFailed($monitor, $period);
        $listener->handle($failedEvent);

        // Fire succeeded event
        $succeededEvent = new UptimeCheckSucceeded($monitor);
        $listener->handle($succeededEvent);

        // Check if both history records were created
        $historyRecords = $service->getHistory($monitor->id, 10, 0);

        // There might be multiple records (from model creation + events), so check the latest two
        $this->assertGreaterThanOrEqual(2, count($historyRecords));
        $this->assertEquals('up', $historyRecords[0]['uptime_status']); // Most recent (succeeded event)
        $this->assertEquals('down', $historyRecords[1]['uptime_status']); // Earlier (failed event)
    }
}
