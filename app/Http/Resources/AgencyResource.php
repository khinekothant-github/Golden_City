<?php

namespace App\Http\Resources;

use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Agency $resource */
class AgencyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'staff' => $this->resource->staff,
            'commission_id' => $this->resource->commission_id,
            'phone' => $this->resource->phone,
            'sale_staff_phone' => $this->resource->sale_staff_phone,
            'commission_scheme' => new CommissionSchemeResource($this->whenLoaded('commissionScheme')),
        ];
    }
}