<?php

namespace App\Http\Resources;

use App\Models\CommissionScheme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read CommissionScheme $resource */
class CommissionSchemeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'rate' => $this->resource->rate,
        ];
    }
}