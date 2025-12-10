<?php

namespace App\Jobs;

use App\Models\Monitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class IncrementMonitorPageViewJob implements ShouldQueue
{
    use Queueable;

    /**
     * The cooldown period in seconds (5 minutes).
     */
    protected const VIEW_COOLDOWN = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $monitorId,
        public string $ipAddress
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Create a unique cache key based on monitor ID and hashed IP
        $cacheKey = $this->getCacheKey();

        // Check if this IP already viewed this monitor recently
        if (Cache::has($cacheKey)) {
            return;
        }

        // Increment the view count
        Monitor::where('id', $this->monitorId)->increment('page_views_count');

        // Set cooldown to prevent duplicate counts
        Cache::put($cacheKey, true, self::VIEW_COOLDOWN);
    }

    /**
     * Get the cache key for rate limiting.
     */
    protected function getCacheKey(): string
    {
        $ipHash = md5($this->ipAddress);

        return "monitor_view_{$this->monitorId}_{$ipHash}";
    }
}
