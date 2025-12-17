<?php

use App\Listeners\SendCustomMonitorNotification;
use App\Models\Monitor;
use App\Models\MonitorIncident;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\MonitorStatusChanged;
use App\Services\AlertPatternService;
use Illuminate\Support\Facades\Notification;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;
use Spatie\UptimeMonitor\Helpers\Period;

beforeEach(function () {
    $this->user = User::factory()->create();

    NotificationChannel::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'email',
        'destination' => 'test@example.com',
        'is_enabled' => true,
    ]);

    Notification::fake();
});

describe('Fibonacci Alert Throttling', function () {

    it('sends alerts only on Fibonacci failure counts', function () {
        $monitor = Monitor::factory()->create([
            'notification_settings' => ['alert_pattern' => AlertPatternService::PATTERN_FIBONACCI],
            'uptime_check_enabled' => true,
            'uptime_check_times_failed_in_a_row' => 0,
        ]);
        $monitor->users()->attach($this->user->id, ['is_active' => true]);

        $listener = app(SendCustomMonitorNotification::class);
        $expectedAlerts = [1, 2, 3, 5, 8]; // Fibonacci numbers up to 10
        $alertCount = 0;

        // Create initial incident
        MonitorIncident::create([
            'monitor_id' => $monitor->id,
            'type' => 'down',
            'started_at' => now(),
            'down_alert_sent' => false,
            'last_alert_at_failure_count' => null,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            Notification::fake(); // Reset for each iteration

            $monitor->uptime_check_times_failed_in_a_row = $i;
            $monitor->save();

            $event = new UptimeCheckFailed($monitor, new Period(now()->subMinutes(5), now()));
            $listener->handle($event);

            if (in_array($i, $expectedAlerts)) {
                Notification::assertSentTo($this->user, MonitorStatusChanged::class);
                $alertCount++;
            } else {
                Notification::assertNotSentTo($this->user, MonitorStatusChanged::class);
            }
        }

        expect($alertCount)->toBe(5);
    });

    it('sends recovery only if DOWN alert was sent', function () {
        $monitor = Monitor::factory()->create([
            'notification_settings' => ['alert_pattern' => AlertPatternService::PATTERN_FIBONACCI],
            'uptime_check_enabled' => true,
        ]);
        $monitor->users()->attach($this->user->id, ['is_active' => true]);

        $listener = app(SendCustomMonitorNotification::class);

        // Test: Alert was sent - recovery should be sent
        $incidentWithAlert = MonitorIncident::factory()->alertSent()->ongoing()->create([
            'monitor_id' => $monitor->id,
        ]);

        $event = new UptimeCheckRecovered($monitor, new Period(now()->subMinutes(10), now()));
        $listener->handle($event);

        Notification::assertSentTo($this->user, MonitorStatusChanged::class);

        // Clean up
        $incidentWithAlert->delete();
        Notification::fake();

        // Test: No alert was sent - recovery should NOT be sent
        MonitorIncident::factory()->noAlertSent()->ongoing()->create([
            'monitor_id' => $monitor->id,
        ]);

        $listener->handle($event);

        Notification::assertNotSentTo($this->user, MonitorStatusChanged::class);
    });

    it('marks incident when DOWN alert is sent', function () {
        $monitor = Monitor::factory()->create([
            'notification_settings' => ['alert_pattern' => AlertPatternService::PATTERN_FIBONACCI],
            'uptime_check_enabled' => true,
            'uptime_check_times_failed_in_a_row' => 1, // Fibonacci number
        ]);
        $monitor->users()->attach($this->user->id, ['is_active' => true]);

        $incident = MonitorIncident::factory()->noAlertSent()->ongoing()->create([
            'monitor_id' => $monitor->id,
        ]);

        $listener = app(SendCustomMonitorNotification::class);
        $event = new UptimeCheckFailed($monitor, new Period(now()->subMinutes(5), now()));
        $listener->handle($event);

        $incident->refresh();
        expect($incident->down_alert_sent)->toBeTrue();
        expect($incident->last_alert_at_failure_count)->toBe(1);
    });

    it('maintains backward compatibility with default pattern', function () {
        // Monitor without explicit alert_pattern setting should send on every failure
        $monitor = Monitor::factory()->create([
            'notification_settings' => null, // No settings
            'uptime_check_enabled' => true,
            'uptime_check_times_failed_in_a_row' => 4, // Not a Fibonacci number
        ]);
        $monitor->users()->attach($this->user->id, ['is_active' => true]);

        MonitorIncident::factory()->ongoing()->create([
            'monitor_id' => $monitor->id,
            'down_alert_sent' => false,
        ]);

        $listener = app(SendCustomMonitorNotification::class);
        $event = new UptimeCheckFailed($monitor, new Period(now()->subMinutes(5), now()));
        $listener->handle($event);

        Notification::assertSentTo($this->user, MonitorStatusChanged::class);
    });

    it('maintains backward compatibility with empty notification_settings', function () {
        $monitor = Monitor::factory()->create([
            'notification_settings' => [],
            'uptime_check_enabled' => true,
            'uptime_check_times_failed_in_a_row' => 6, // Not a Fibonacci number
        ]);
        $monitor->users()->attach($this->user->id, ['is_active' => true]);

        MonitorIncident::factory()->ongoing()->create([
            'monitor_id' => $monitor->id,
            'down_alert_sent' => false,
        ]);

        $listener = app(SendCustomMonitorNotification::class);
        $event = new UptimeCheckFailed($monitor, new Period(now()->subMinutes(5), now()));
        $listener->handle($event);

        Notification::assertSentTo($this->user, MonitorStatusChanged::class);
    });

    it('does not send alert on non-Fibonacci failure count', function () {
        $monitor = Monitor::factory()->create([
            'notification_settings' => ['alert_pattern' => AlertPatternService::PATTERN_FIBONACCI],
            'uptime_check_enabled' => true,
            'uptime_check_times_failed_in_a_row' => 4, // Not a Fibonacci number
        ]);
        $monitor->users()->attach($this->user->id, ['is_active' => true]);

        MonitorIncident::factory()->ongoing()->create([
            'monitor_id' => $monitor->id,
            'down_alert_sent' => false,
        ]);

        $listener = app(SendCustomMonitorNotification::class);
        $event = new UptimeCheckFailed($monitor, new Period(now()->subMinutes(5), now()));
        $listener->handle($event);

        Notification::assertNotSentTo($this->user, MonitorStatusChanged::class);
    });
});
