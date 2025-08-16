<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StatusPageResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null; // <--- TAMBAHKAN BARIS INI

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'path' => $this->path,
            'custom_domain' => $this->custom_domain,
            'custom_domain_verified' => $this->custom_domain_verified,
            'force_https' => $this->force_https,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'monitors' => MonitorResource::collection($this->whenLoaded('monitors')),
        ];
    }
}
