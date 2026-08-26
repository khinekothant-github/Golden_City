<?php

namespace App\Models;

use App\Enums\UnitStatus;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['phase_id', 'building_id', 'name', 'type', 'room_description', 'base_price', 'status'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'base_price' => 0,
        'status' => 'available',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'status' => UnitStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Set only when the unit hangs directly off a phase.
     *
     * @return BelongsTo<Phase, $this>
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * Multiple rows per unit = resale history.
     *
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * @return HasOne<Sale, $this>
     */
    public function latestSale(): HasOne
    {
        return $this->hasOne(Sale::class)->latestOfMany();
    }

    /**
     * Customers who preferred / liked / disliked this unit.
     *
     * @return BelongsToMany<Customer, $this, CustomerUnitInterest>
     */
    public function interestedCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_unit_interests')
            ->using(CustomerUnitInterest::class)
            ->withPivot('sentiment', 'comment');
    }

    /**
     * @param  Builder<Unit>  $query
     * @return Builder<Unit>
     */
    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->where('status', UnitStatus::Available);
    }
}
