<?php

namespace App\Http\Resources;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Unit $resource */
class UnitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'building_id' => $this->resource->building_id,
            'phase_id' => $this->resource->phase_id,
            'name' => $this->resource->name,
            'type' => $this->resource->type,
            'room_description' => $this->resource->room_description,
            'base_price' => $this->resource->base_price,
            'status' => $this->resource->status->value,
            'building' => new BuildingResource($this->whenLoaded('building')),
            'phase' => new PhaseResource($this->whenLoaded('phase')),
        ];
    }
}