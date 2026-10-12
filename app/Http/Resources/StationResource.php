<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => (string) $this->name,
            'river_basin' => (string) $this->river_basin,
            'location' => (string) $this->location,
            'current_level_cm' => (float) $this->current_level_cm,
            'flow_rate_m3s' => (float) $this->flow_rate_m3s,
            'alert_status' => (string) $this->alert_status,
            'thresholds_cm' => is_array($this->thresholds_cm)
                ? $this->thresholds_cm
                : (json_decode((string) $this->thresholds_cm, true) ?? []),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
