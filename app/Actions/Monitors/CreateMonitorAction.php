<?php

namespace App\Actions\Monitors;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class CreateMonitorAction
{
    /**
     * Create a monitor, attach tags, attach user, and trigger initial certificate check.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): Monitor
    {
        return DB::transaction(function () use ($user, $data) {
            $monitor = Monitor::create([
                'url' => $data['url'],
                'is_public' => (bool) ($data['is_public'] ?? false),
                'uptime_check_enabled' => (bool) ($data['uptime_check_enabled'] ?? true),
                'certificate_check_enabled' => (bool) ($data['certificate_check_enabled'] ?? true),
                'domain_expiration_check_enabled' => (bool) ($data['domain_expiration_check_enabled'] ?? false),
                'uptime_check_interval_in_minutes' => (int) ($data['uptime_check_interval'] ?? 5),
            ]);

            if (! empty($data['tags'])) {
                $monitor->attachTags($data['tags']);
            }

            // Check certificate using Artisan command
            Artisan::call('monitor:check-certificate', [
                '--url' => $monitor->url,
            ]);

            cache()->forget('monitor_list_page_1_per_page_15_user_'.$user->id);

            return $monitor;
        });
    }
}
