<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleMonitorResource extends JsonResource
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
            'host' => $this->host,
            'uptime_status' => $this->uptime_status,
            'uptime_check_enabled' => (bool) $this->uptime_check_enabled,
            'favicon' => $this->favicon,
            'last_check_date' => $this->uptime_last_check_date,
            'last_check_date_human' => $this->uptime_last_check_date ? $this->uptime_last_check_date->diffForHumans() : null,
            'today_uptime_percentage' => $this->getTodayUptimePercentage(),
            'tags' => $this->when($this->relationLoaded('tags'), function () {
                return $this->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'type' => $tag->type,
                        'color' => $tag->color ?? null,
                    ];
                });
            }),
            'latest_history' => $this->whenLoaded('latestHistory', function () {
                return $this->latestHistory ? new MonitorHistoryResource($this->latestHistory) : null;
            }),
            'statistics' => $this->whenLoaded('statistics', function () {
                return [
                    'uptime_24h' => $this->statistics->uptime_24h ?? null,
                    'avg_response_time_24h' => $this->statistics->avg_response_time_24h ?? null,
                    'incidents_24h' => $this->statistics->incidents_24h ?? 0,
                ];
            }),
        ];
    }

    /**
     * Get today's uptime percentage safely.
     */
    protected function getTodayUptimePercentage(): float
    {
        if ($this->relationLoaded('uptimeDaily')) {
            return $this->uptimeDaily?->uptime_percentage ?? 0;
        }

        return 0;
    }
}
