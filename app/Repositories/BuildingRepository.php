<?php

namespace App\Repositories;

use App\Models\Building;
use Illuminate\Pagination\LengthAwarePaginator;

class BuildingRepository
{
    public function paginate(?int $phaseId = null): LengthAwarePaginator
    {
        return Building::query()
            ->with('phase')
            ->withCount('units')
            ->when($phaseId !== null, fn ($query) => $query->where('phase_id', $phaseId))
            ->orderBy('name')
            ->paginate();
    }

    public function find(int $id): ?Building
    {
        return Building::with('phase')->withCount('units')->find($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Building
    {
        return Building::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Building $building, array $data): Building
    {
        $building->update($data);

        return $building->load('phase')->loadCount('units');
    }

    public function delete(Building $building): void
    {
        $building->delete();
    }

    public function hasUnits(Building $building): bool
    {
        return $building->units()->withTrashed()->exists();
    }
}