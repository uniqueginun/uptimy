<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EndpointResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'location' => $this->resource->uri,
            'frequency' => $this->resource->interval,
            'last_check' => $this->resource->last_run_at,
            'last_status' => $this->resource->lastCheck?->http_status_code,
            'last_check_status' => $this->resource->lastCheck?->status?->value,
            'uptime' => $this->resource->uptime_percentage,
            'uptime_24h' => $this->resource->uptime_percentage_24h,
        ];
    }
}
