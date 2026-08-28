<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusPageMonitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rawUrl = $this->getRawOriginal('url');
        $host = parse_url($rawUrl, PHP_URL_HOST);
        $host = str_replace('www.', '', $host ?? $rawUrl);

        return [
            'id' => $this->id,
            'name' => $rawUrl,
            'url' => $rawUrl,
            'host' => $host,
            'uptime_status' => $this->uptime_status,
            'uptime_check_enabled' => (bool) $this->uptime_check_enabled,
            'favicon' => $this->favicon ?: ($host ? "https://s2.googleusercontent.com/s2/favicons?domain={$host}&sz=32" : null),
            'last_check_date' => $this->uptime_last_check_date,
            'certificate_check_enabled' => (bool) $this->certificate_check_enabled,
            'certificate_status' => $this->certificate_status,
            'domain_expiration_check_enabled' => (bool) $this->domain_expiration_check_enabled,
            'domain_expiration_date' => $this->domain_expiration_date,
            'latest_history' => $this->whenLoaded('latestHistory', function () {
                return $this->latestHistory ? new MonitorHistoryResource($this->latestHistory) : null;
            }),
            'uptimes_daily' => $this->whenLoaded('uptimesDaily', function () {
                return $this->uptimesDaily->map(function ($uptime) {
                    return [
                        'date' => $uptime->date->toDateString(),
                        'uptime_percentage' => (float) $uptime->uptime_percentage,
                    ];
                });
            }),
        ];
    }
}
