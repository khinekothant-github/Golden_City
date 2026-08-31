<?php

namespace App\Repositories;

use App\Models\Agency;
use Illuminate\Pagination\LengthAwarePaginator;

class AgencyRepository
{
    public function paginate(): LengthAwarePaginator
    {
        return Agency::with('commissionScheme')->orderBy('name')->paginate();
    }

    public function find(int $id): ?Agency
    {
        return Agency::with('commissionScheme')->find($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Agency
    {
        return Agency::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Agency $agency, array $data): Agency
    {
        $agency->update($data);

        return $agency->load('commissionScheme');
    }

    public function delete(Agency $agency): void
    {
        $agency->delete();
    }
}