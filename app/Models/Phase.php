<?php

namespace App\Models;

use App\Enums\PhaseType;
use Database\Factories\PhaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'location', 'type'])]
class Phase extends Model
{
    /** @use HasFactory<PhaseFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'type' => 'residential',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PhaseType::class,
        ];
    }

    /**
     * @return HasMany<Building, $this>
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    /**
     * Units that hang directly off this phase with no building (landed plots).
     *
     * @return HasMany<Unit, $this>
     */
    public function directUnits(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
