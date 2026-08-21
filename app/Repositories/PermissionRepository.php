<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class PermissionRepository
{
    /** @return Collection<int, Permission> */
    public function all(): Collection
    {
        return Permission::orderBy('name')->get();
    }
}
