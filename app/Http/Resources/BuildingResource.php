<?php

namespace App\Http\Resources;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Building $resource */
class BuildingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'phase_id' => $this->resource->phase_id,
            'name' => $this->resource->name,
            'building_type' => $this->resource->building_type,
            'unit_count' => $this->when(isset($this->resource->units_count), $this->resource->units_count),
            'phase' => new PhaseResource($this->whenLoaded('phase')),
        ];
    }
}