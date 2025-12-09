<?php

namespace App\Jobs;

use App\Services\TelemetryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelemetryPingJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(TelemetryService $telemetryService): void
    {
        // Double-check telemetry is still enabled
        if (! $telemetryService->isEnabled()) {
            Log::debug('Telemetry is disabled, skipping ping.');

            return;
        }

        $data = $telemetryService->collectData();
        $endpoint = $telemetryService->getEndpoint();

        // Debug mode: log instead of send
        if (config('telemetry.debug')) {
            Log::info('Telemetry Debug - Would send data:', $data);
            $telemetryService->recordPing();

            return;
        }

        try {
            $response = Http::timeout(config('telemetry.timeout', 10))
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'Uptime-Kita/'.config('app.last_update', 'unknown'),
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $data);

            if ($response->successful()) {
                $telemetryService->recordPing();
                Log::debug('Telemetry ping sent successfully.');
            } else {
                Log::warning('Telemetry ping failed with status: '.$response->status());
            }
        } catch (\Exception $e) {
            // Silent failure - telemetry should never impact the main app
            Log::debug('Telemetry ping failed: '.$e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Final failure after all retries - just log quietly
        Log::info('Telemetry ping failed after all retries: '.$exception->getMessage());
    }
}
