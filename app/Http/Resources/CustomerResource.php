<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Customer $resource */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'contact_number' => $this->resource->contact_number,
            'type' => $this->resource->type->value,
            'appointment_time' => $this->resource->appointment_time?->toISOString(),
            'number_of_visits' => $this->resource->number_of_visits,
            'purchase_purpose' => $this->resource->purchase_purpose?->value,
            'sale_persons' => UserResource::collection($this->whenLoaded('salePersons')),
            'interests' => $this->whenLoaded('interestedUnits', function () {
                return $this->resource->interestedUnits->map(fn ($unit) => [
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'sentiment' => is_string($unit->pivot->sentiment) ? $unit->pivot->sentiment : $unit->pivot->sentiment->value,
                    'comment' => $unit->pivot->comment,
                ])->values();
            }),
            'source' => new SourceResource($this->whenLoaded('source')),
        ];
    }
}
