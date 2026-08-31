<?php

namespace App\Repositories;

use App\Models\Unit;
use Illuminate\Pagination\LengthAwarePaginator;

class UnitRepository
{
    public function paginate(?int $phaseId = null, ?int $buildingId = null): LengthAwarePaginator
    {
        return Unit::query()
            ->with('building.phase', 'phase')
            ->when($phaseId !== null, fn ($query) => $query->where('phase_id', $phaseId))
            ->when($buildingId !== null, fn ($query) => $query->where('building_id', $buildingId))
            ->orderBy('name')
            ->paginate();
    }

    public function find(int $id): ?Unit
    {
        return Unit::with('building.phase', 'phase')->find($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);

        return $unit->load('building.phase', 'phase');
    }

    public function delete(Unit $unit): void
    {
        $unit->delete();
    }

    public function hasSales(Unit $unit): bool
    {
        return $unit->sales()->withTrashed()->exists();
    }
}