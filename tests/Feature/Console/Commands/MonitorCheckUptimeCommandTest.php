<?php

use App\Console\Commands\MonitorCheckUptime;
use App\Models\Monitor;
use Illuminate\Support\Facades\Log;

describe('MonitorCheckUptime', function () {
    it('runs successfully', function () {
        Monitor::factory()->create(['uptime_check_enabled' => true]);

        $this->artisan('monitor:check-uptime')
            ->assertSuccessful();
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
