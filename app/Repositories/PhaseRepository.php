<?php

namespace App\Repositories;

use App\Models\Phase;
use Illuminate\Pagination\LengthAwarePaginator;

class PhaseRepository
{
    public function paginate(): LengthAwarePaginator
    {
        return Phase::withCount('buildings')->withCount('directUnits')->orderBy('name')->paginate();
    }

    public function find(int $id): ?Phase
    {
        return Phase::withCount('buildings')->withCount('directUnits')->find($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Phase
    {
        return Phase::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Phase $phase, array $data): Phase
    {
        $phase->update($data);

        return $phase->loadCount('buildings')->loadCount('directUnits');
    }

    public function delete(Phase $phase): void
    {
        $phase->delete();
    }

    public function hasChildren(Phase $phase): bool
    {
        return $phase->buildings()->withTrashed()->exists()
            || $phase->directUnits()->withTrashed()->exists();
    }
}