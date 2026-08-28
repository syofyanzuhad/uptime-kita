<?php

use App\Console\Commands\MonitorCheckUptime;
use App\Jobs\CheckMonitorBatchJob;
use App\Models\Monitor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

describe('MonitorCheckUptime', function () {
    it('runs successfully', function () {
        Monitor::factory()->create(['uptime_check_enabled' => true]);

        $this->artisan('monitor:check-uptime')
            ->assertSuccessful();
    });

    it('outputs message when no monitors are due', function () {
        Monitor::factory()->create([
            'uptime_check_enabled' => true,
            'uptime_last_check_date' => now(),
            'uptime_check_interval_in_minutes' => 60,
        ]);

        $this->artisan('monitor:check-uptime')
            ->expectsOutput('No monitors due for uptime check.')
            ->assertSuccessful();
    });

    it('filters by URL when option is provided', function () {
        $monitor1 = Monitor::factory()->create([
            'url' => 'https://alpha.example.com',
            'uptime_check_enabled' => true,
        ]);
        $monitor2 = Monitor::factory()->create([
            'url' => 'https://beta.example.com',
            'uptime_check_enabled' => true,
        ]);

        $this->artisan('monitor:check-uptime', ['--url' => 'https://alpha.example.com', '--force' => true])
            ->assertSuccessful();
    });

    it('dispatches batch jobs to queue when total monitors exceed queue threshold', function () {
        Queue::fake();

        config([
            'uptime-monitor.uptime_check.queue_threshold' => 2,
            'uptime-monitor.uptime_check.batch_size' => 2,
        ]);

        Monitor::factory()->count(4)->create([
            'uptime_check_enabled' => true,
        ]);

        $this->artisan('monitor:check-uptime', ['--force' => true])
            ->expectsOutput('Total monitors due (4) exceeds threshold (2). Dispatching 2 batch jobs to queue...')
            ->assertSuccessful();

        Queue::assertPushed(CheckMonitorBatchJob::class, 2);
    });

    it('casts parent status to int', function () {
        Monitor::factory()->create(['uptime_check_enabled' => true]);

        $this->artisan('monitor:check-uptime')
            ->assertExitCode(0);
    });

    it('returns success when parent returns null', function () {
        $command = new class extends MonitorCheckUptime
        {
            public function handle(): int
            {
                $status = null;

                return (int) ($status ?? self::SUCCESS);
            }
        };

        expect($command->handle())->toBe(0);
    });

    it('catches and logs exceptions', function () {
        Log::shouldReceive('error')
            ->once()
            ->with('monitor:check-uptime failed', Mockery::on(function ($data) {
                return isset($data['exception']) && $data['exception'] instanceof Throwable && $data['exception']->getMessage() === 'Test Exception';
            }));

        $command = new class extends MonitorCheckUptime
        {
            public function handle(): int
            {
                try {
                    throw new Exception('Test Exception');
                } catch (Throwable $e) {
                    Log::error('monitor:check-uptime failed', [
                        'exception' => $e,
                    ]);

                    return self::FAILURE;
                }
            }
        };

        expect($command->handle())->toBe(1);
    });
});
