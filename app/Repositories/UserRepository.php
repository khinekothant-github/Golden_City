<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function find(int $id): ?User
    {
        return User::with('roles')->find($id);
    }

    public function paginate(): LengthAwarePaginator
    {
        return User::with('roles')->orderBy('name')->paginate();
    }

    /** @return Collection<int, User> */
    public function all(): Collection
    {
        return User::with('roles')->orderBy('name')->get();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->load('roles');
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
