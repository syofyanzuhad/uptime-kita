<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->raw_url,
            'url' => $this->raw_url,
            'uptime_status' => $this->uptime_status,
            'uptime_check_enabled' => (bool) $this->uptime_check_enabled,
            'favicon' => $this->favicon,
            'last_check_date' => $this->uptime_last_check_date,
            'last_check_date_human' => $this->uptime_last_check_date ? $this->uptime_last_check_date->diffForHumans() : null,
            'certificate_check_enabled' => (bool) $this->certificate_check_enabled,
            'certificate_status' => $this->certificate_status,
            'certificate_expiration_date' => $this->certificate_expiration_date,
            'down_for_events_count' => $this->getDownEventsCount(),
            'uptime_check_interval' => $this->uptime_check_interval_in_minutes,
            'is_subscribed' => $this->is_subscribed,
            'is_public' => $this->is_public,
            'today_uptime_percentage' => $this->getTodayUptimePercentage(),
            'uptime_status_last_change_date' => $this->uptime_status_last_change_date,
            'uptime_check_failure_reason' => $this->uptime_check_failure_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'histories' => null, // Will be set manually if needed
            'latest_history' => null, // Will be set manually if needed
            'uptimes_daily' => $this->whenLoaded('uptimesDaily', function () {
                return $this->uptimesDaily->map(function ($uptime) {
                    return [
                        'date' => $uptime->date->toDateString(),
                        'uptime_percentage' => $uptime->uptime_percentage,
                    ];
                });
            }),
        ];
    }

    /**
     * Get the count of down events from histories.
     */
    protected function getDownEventsCount(): int
    {
        // This will be calculated manually when histories are available
        return 0;
    }

    /**
     * Get today's uptime percentage safely.
     */
    protected function getTodayUptimePercentage(): float
    {
        // If uptimeDaily is loaded, use it
        if ($this->relationLoaded('uptimeDaily')) {
            return $this->uptimeDaily?->uptime_percentage ?? 0;
        }

        // Otherwise, return 0 to avoid lazy loading
        return 0;
    }
}
