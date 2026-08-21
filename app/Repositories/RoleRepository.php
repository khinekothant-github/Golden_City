<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class RoleRepository
{
    /** @return Collection<int, Role> */
    public function all(): Collection
    {
        return Role::with('permissions')->orderBy('name')->get();
    }

    public function find(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    public function findByName(string $name): ?Role
    {
        return Role::findByName($name);
    }

    /** @param list<string> $permissionNames */
    public function create(string $name, array $permissionNames): Role
    {
        $role = Role::create(['name' => $name]);
        $role->syncPermissions($permissionNames);

        return $role->load('permissions');
    }

    /** @param list<string> $permissionNames */
    public function update(Role $role, string $name, array $permissionNames): Role
    {
        $role->update(['name' => $name]);
        $role->syncPermissions($permissionNames);

        return $role->load('permissions');
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function hasUsers(Role $role): bool
    {
        return $role->users()->exists();
    }
}
