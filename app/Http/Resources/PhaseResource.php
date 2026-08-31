<?php

namespace App\Http\Resources;

use App\Models\Phase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Phase $resource */
class PhaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'location' => $this->resource->location,
            'type' => $this->resource->type->value,
            'buildings_count' => $this->when(isset($this->resource->buildings_count), $this->resource->buildings_count),
            'units_count' => $this->when(isset($this->resource->direct_units_count), $this->resource->direct_units_count),
        ];
    }
}