<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Enums\PurchasePurpose;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'email', 'contact_number', 'type', 'appointment_time', 'number_of_visits', 'purchase_purpose'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'number_of_visits' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
            'purchase_purpose' => PurchasePurpose::class,
            'appointment_time' => 'datetime',
        ];
    }

    /**
     * Many sale persons per customer.
     *
     * @return BelongsToMany<User, $this>
     */
    public function salePersons(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_sale_person', 'customer_id', 'sale_person_id');
    }

    /**
     * Preferred / liked / disliked units with their comments.
     * Core rule: no unit FK lives on customers — everything goes through this pivot or sales.
     *
     * @return BelongsToMany<Unit, $this, CustomerUnitInterest>
     */
    public function interestedUnits(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'customer_unit_interests')
            ->using(CustomerUnitInterest::class)
            ->withPivot('sentiment', 'comment');
    }

    /**
     * @return HasOne<Source, $this>
     */
    public function source(): HasOne
    {
        return $this->hasOne(Source::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Count one completed visit against this customer.
     *
     * @return $this
     */
    public function recordVisit(): static
    {
        $this->increment('number_of_visits');

        return $this;
    }
}
