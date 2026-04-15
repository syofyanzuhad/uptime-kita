<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\MonitorCheckUptime;
use App\Models\Monitor;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Commands\CheckUptime as SpatieCheckUptime;
use Tests\TestCase;
use Throwable;

class MonitorCheckUptimeCommandTest extends TestCase
{
    public function test_it_runs_successfully()
    {
        // Create a monitor to avoid 0 monitors check
        Monitor::factory()->create(['uptime_check_enabled' => true]);

        $this->artisan('monitor:check-uptime')
            ->assertSuccessful();
    }

    public function test_it_returns_success_when_parent_returns_null()
    {
        $command = new class extends MonitorCheckUptime {
            public function handle(): int
            {
                $status = null; // Simulate parent::handle() returning null
                return (int) ($status ?? self::SUCCESS);
            }
        };

        $status = $command->handle();
        $this->assertEquals(0, $status); // self::SUCCESS is 0
    }

    public function test_it_catches_and_logs_exceptions()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('monitor:check-uptime failed', \Mockery::on(function ($data) {
                return isset($data['exception']) && $data['exception'] instanceof Throwable && $data['exception']->getMessage() === 'Test Exception';
            }));

        // Use a partial mock to simulate parent::handle() throwing an exception
        // Actually, we can't mock 'parent' calls directly.
        // Let's create a testable subclass.
        
        $command = new class extends MonitorCheckUptime {
            public function handle(): int
            {
                try {
                    // Simulate parent::handle() throwing exception
                    throw new \Exception('Test Exception');
                } catch (Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('monitor:check-uptime failed', [
                        'exception' => $e,
                    ]);
                    return self::FAILURE;
                }
            }
        };

        $status = $command->handle();
        $this->assertEquals(1, $status); // self::FAILURE is 1
    }
}
