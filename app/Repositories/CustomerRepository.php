<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\CustomerUnitInterest;
use App\Models\Source;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerRepository
{
    /** @var list<string> */
    private const RELATIONS = ['salePersons', 'interestedUnits', 'source.agency', 'source.event'];

    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Customer::query()
            ->with(self::RELATIONS)
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate();
    }

    public function find(int $id): ?Customer
    {
        return Customer::with(self::RELATIONS)->find($id);
    }

    /** @param array<string, mixed> $data */
    public function createWithBooking(array $data): Customer
    {
        $salePersonIds = $data['sale_person_ids'] ?? [];
        $preferredUnitIds = $data['preferred_unit_ids'] ?? [];
        unset($data['sale_person_ids'], $data['preferred_unit_ids']);

        return DB::transaction(function () use ($data, $salePersonIds, $preferredUnitIds) {
            $customer = Customer::create($data);

            if ($salePersonIds !== []) {
                $customer->salePersons()->sync($salePersonIds);
            }

            foreach (array_unique($preferredUnitIds) as $unitId) {
                CustomerUnitInterest::create([
                    'customer_id' => $customer->id,
                    'unit_id' => $unitId,
                    'sentiment' => 'preferred',
                ]);
            }

            return $customer->load(self::RELATIONS);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Customer $customer, array $data): Customer
    {
        $salePersonIds = $data['sale_person_ids'] ?? null;
        unset($data['sale_person_ids']);

        $customer->update($data);

        if (is_array($salePersonIds)) {
            $customer->salePersons()->sync($salePersonIds);
        }

        return $customer->load(self::RELATIONS);
    }

    /** @param array<string, mixed> $data */
    public function recordVisit(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->recordVisit();

            if (array_key_exists('purchase_purpose', $data) && $data['purchase_purpose'] !== null) {
                $customer->update(['purchase_purpose' => $data['purchase_purpose']]);
            }

            foreach ($data['feedbacks'] ?? [] as $feedback) {
                $exists = CustomerUnitInterest::where('customer_id', $customer->id)
                    ->where('unit_id', $feedback['unit_id'])
                    ->where('sentiment', $feedback['sentiment'])
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'feedbacks' => ['Feedback for this unit and sentiment already exists.'],
                    ]);
                }

                CustomerUnitInterest::create([
                    'customer_id' => $customer->id,
                    'unit_id' => $feedback['unit_id'],
                    'sentiment' => $feedback['sentiment'],
                    'comment' => $feedback['comment'] ?? null,
                ]);
            }

            if (array_key_exists('source', $data) && is_array($data['source'])) {
                Source::updateOrCreate(
                    ['customer_id' => $customer->id],
                    [
                        'category' => $data['source']['category'],
                        'sub_category' => $data['source']['sub_category'] ?? null,
                        'agency_id' => $data['source']['agency_id'] ?? null,
                        'event_id' => $data['source']['event_id'] ?? null,
                    ]
                );
            }

            return $customer->load(self::RELATIONS);
        });
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    public function hasSales(Customer $customer): bool
    {
        return $customer->sales()->withTrashed()->exists();
    }
}
