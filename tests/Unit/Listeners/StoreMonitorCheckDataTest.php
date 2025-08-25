<?php

use App\Listeners\StoreMonitorCheckData;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorIncident;
use App\Services\MonitorPerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;
use Spatie\UptimeMonitor\Events\UptimeCheckSucceeded;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);
    
    $this->performanceService = mock(MonitorPerformanceService::class);
    $this->listener = new StoreMonitorCheckData($this->performanceService);
});

describe('StoreMonitorCheckData', function () {
    describe('handle', function () {
        it('stores successful check data', function () {
            $event = new UptimeCheckSucceeded($this->monitor);
            
            $this->performanceService
                ->shouldReceive('updateHourlyMetrics')
                ->once()
                ->with($this->monitor->id, \Mockery::type('int'), true);
            
            $this->listener->handle($event);
            
            $history = MonitorHistory::where('monitor_id', $this->monitor->id)->first();
            expect($history)->not->toBeNull();
            expect($history->uptime_status)->toBe('up');
            expect($history->response_time)->toBeGreaterThan(0);
            expect($history->status_code)->toBe(200);
            expect($history->message)->toBeNull();
        });

        it('stores failed check data', function () {
            $this->monitor->update(['uptime_check_failure_reason' => 'Connection timeout']);
            $event = new UptimeCheckFailed($this->monitor);
            
            $this->performanceService
                ->shouldReceive('updateHourlyMetrics')
                ->once()
                ->with($this->monitor->id, \Mockery::type('int'), false);
            
            $this->listener->handle($event);
            
            $history = MonitorHistory::where('monitor_id', $this->monitor->id)->first();
            expect($history)->not->toBeNull();
            expect($history->uptime_status)->toBe('down');
            expect($history->response_time)->toBeGreaterThan(0);
            expect($history->status_code)->toBe(0);
            expect($history->message)->toBe('Connection timeout');
        });

        it('stores recovered check data', function () {
            $event = new UptimeCheckRecovered($this->monitor);
            
            $this->performanceService
                ->shouldReceive('updateHourlyMetrics')
                ->once()
                ->with($this->monitor->id, \Mockery::type('int'), true);
            
            $this->listener->handle($event);
            
            $history = MonitorHistory::where('monitor_id', $this->monitor->id)->first();
            expect($history)->not->toBeNull();
            expect($history->uptime_status)->toBe('up');
            expect($history->response_time)->toBeGreaterThan(0);
            expect($history->status_code)->toBe(200);
            expect($history->message)->toBeNull();
        });

        it('creates incident on failure', function () {
            $this->monitor->update(['uptime_check_failure_reason' => 'HTTP 500 Error']);
            $event = new UptimeCheckFailed($this->monitor);
            
            $this->performanceService
                ->shouldReceive('updateHourlyMetrics')
                ->once();
            
            $this->listener->handle($event);
            
            $incident = MonitorIncident::where('monitor_id', $this->monitor->id)->first();
            expect($incident)->not->toBeNull();
            expect($incident->type)->toBe('down');
            expect($incident->started_at)->not->toBeNull();
            expect($incident->ended_at)->toBeNull();
            expect($incident->reason)->toBe('HTTP 500 Error');
        });

        it('does not create duplicate incident when already ongoing', function () {
            // Create existing incident
            MonitorIncident::create([
                'monitor_id' => $this->monitor->id,
                'type' => 'down',
                'started_at' => now()->subMinutes(5),
                'reason' => 'Previous failure',
            ]);
            
            $this->monitor->update(['uptime_check_failure_reason' => 'New failure']);
            $event = new UptimeCheckFailed($this->monitor);
            
            $this->performanceService
                ->shouldReceive('updateHourlyMetrics')
                ->once();
            
            $this->listener->handle($event);
            
            $incidentCount = MonitorIncident::where('monitor_id', $this->monitor->id)->count();
            expect($incidentCount)->toBe(1);
        });

        it('ends ongoing incident on recovery', function () {
            // Create ongoing incident
            $incident = MonitorIncident::create([
                'monitor_id' => $this->monitor->id,
                'type' => 'down',
                'started_at' => now()->subMinutes(5),
                'reason' => 'Previous failure',
            ]);
            
            $event = new UptimeCheckRecovered($this->monitor);
            
            $this->performanceService
                ->shouldReceive('updateHourlyMetrics')
                ->once();
            
            $this->listener->handle($event);
            
            $incident->refresh();
            expect($incident->ended_at)->not->toBeNull();
        });

        it('extracts status code from failure reason', function () {
            $this->monitor->update(['uptime_check_failure_reason' => 'HTTP 404 Not Found']);
            $event = new UptimeCheckFailed($this->monitor);
            
            $this->performanceService
                ->shouldReceive('updateHourlyMetrics')
                ->once();
            
            $this->listener->handle($event);
            
            $history = MonitorHistory::where('monitor_id', $this->monitor->id)->first();
            expect($history->status_code)->toBe(404);
        });
    });

    describe('extractResponseTime', function () {
        it('returns realistic values for successful checks', function () {
            $event = new UptimeCheckSucceeded($this->monitor);
            
            $reflection = new ReflectionClass($this->listener);
            $method = $reflection->getMethod('extractResponseTime');
            $method->setAccessible(true);
            
            $responseTime = $method->invoke($this->listener, $event);
            
            expect($responseTime)->toBeGreaterThanOrEqual(100);
            expect($responseTime)->toBeLessThanOrEqual(500);
        });

        it('returns higher values for failed checks', function () {
            $event = new UptimeCheckFailed($this->monitor);
            
            $reflection = new ReflectionClass($this->listener);
            $method = $reflection->getMethod('extractResponseTime');
            $method->setAccessible(true);
            
            $responseTime = $method->invoke($this->listener, $event);
            
            expect($responseTime)->toBeGreaterThanOrEqual(1000);
            expect($responseTime)->toBeLessThanOrEqual(30000);
        });
    });

    describe('extractStatusCode', function () {
        it('returns 200 for successful checks', function () {
            $event = new UptimeCheckSucceeded($this->monitor);
            
            $reflection = new ReflectionClass($this->listener);
            $method = $reflection->getMethod('extractStatusCode');
            $method->setAccessible(true);
            
            $statusCode = $method->invoke($this->listener, $event);
            
            expect($statusCode)->toBe(200);
        });

        it('extracts status code from failure reason', function () {
            $this->monitor->update(['uptime_check_failure_reason' => 'HTTP 503 Service Unavailable']);
            $event = new UptimeCheckFailed($this->monitor);
            
            $reflection = new ReflectionClass($this->listener);
            $method = $reflection->getMethod('extractStatusCode');
            $method->setAccessible(true);
            
            $statusCode = $method->invoke($this->listener, $event);
            
            expect($statusCode)->toBe(503);
        });

        it('returns 0 for connection failures', function () {
            $this->monitor->update(['uptime_check_failure_reason' => 'Connection timeout']);
            $event = new UptimeCheckFailed($this->monitor);
            
            $reflection = new ReflectionClass($this->listener);
            $method = $reflection->getMethod('extractStatusCode');
            $method->setAccessible(true);
            
            $statusCode = $method->invoke($this->listener, $event);
            
            expect($statusCode)->toBe(0);
        });
    });

    describe('determineStatus', function () {
        it('returns up for successful checks', function () {
            $event = new UptimeCheckSucceeded($this->monitor);
            
            $reflection = new ReflectionClass($this->listener);
            $method = $reflection->getMethod('determineStatus');
            $method->setAccessible(true);
            
            $status = $method->invoke($this->listener, $event);
            
            expect($status)->toBe('up');
        });

        it('returns up for recovered checks', function () {
            $event = new UptimeCheckRecovered($this->monitor);
            
            $reflection = new ReflectionClass($this->listener);
            $method = $reflection->getMethod('determineStatus');
            $method->setAccessible(true);
            
            $status = $method->invoke($this->listener, $event);
            
            expect($status)->toBe('up');
        });

        it('returns down for failed checks', function () {
            $event = new UptimeCheckFailed($this->monitor);
            
            $reflection = new ReflectionClass($this->listener);
            $method = $reflection->getMethod('determineStatus');
            $method->setAccessible(true);
            
            $status = $method->invoke($this->listener, $event);
            
            expect($status)->toBe('down');
        });
    });
});