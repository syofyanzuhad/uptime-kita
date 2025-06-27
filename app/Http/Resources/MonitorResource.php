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
            'url' => $this->raw_url,
            'uptime_status' => $this->uptime_status,
            'uptime_check_enabled' => (bool) $this->uptime_check_enabled,
            'favicon' => $this->favicon,
            'last_check_date' => $this->uptime_last_check_date,
            'certificate_check_enabled' => (bool) $this->certificate_check_enabled,
            'certificate_status' => $this->certificate_status,
            'certificate_expiration_date' => $this->certificate_expiration_date,
            'down_for_events_count' => $this->down_for_events_count,
            'uptime_check_interval' => $this->uptime_check_interval_in_minutes,
            'is_subscribed' => $this->is_subscribed,
            'is_public' => $this->is_public,
            'histories' => $this->whenLoaded('histories', function () {
                return $this->histories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'status' => $history->status,
                        'created_at' => $history->created_at,
                    ];
                });
            }),
        ];
    }
}
