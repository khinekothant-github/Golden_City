<?php

namespace App\Repositories;

use App\Models\CommissionScheme;
use Illuminate\Pagination\LengthAwarePaginator;

class CommissionSchemeRepository
{
    public function paginate(): LengthAwarePaginator
    {
        return CommissionScheme::orderBy('name')->paginate();
    }

    public function find(int $id): ?CommissionScheme
    {
        return CommissionScheme::find($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): CommissionScheme
    {
        return CommissionScheme::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(CommissionScheme $commissionScheme, array $data): CommissionScheme
    {
        $commissionScheme->update($data);

        return $commissionScheme;
    }

    public function delete(CommissionScheme $commissionScheme): void
    {
        $commissionScheme->delete();
    }
}