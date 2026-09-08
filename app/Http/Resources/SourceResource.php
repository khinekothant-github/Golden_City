<?php

namespace App\Http\Resources;

use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Source $resource */
class SourceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'category' => $this->resource->category,
            'sub_category' => $this->resource->sub_category,
            'agency_id' => $this->resource->agency_id,
            'event_id' => $this->resource->event_id,
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'event' => new EventResource($this->whenLoaded('event')),
        ];
    }
}
