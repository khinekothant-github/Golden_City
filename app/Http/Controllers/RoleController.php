<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Repositories\RoleRepository;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly RoleRepository $roles) {}

    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection($this->roles->all());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        $role = $this->roles->create($data['name'], $data['permissions']);

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    public function show(Role $role): RoleResource
    {
        return new RoleResource($this->roles->find($role->id));
    }

    public function update(Request $request, Role $role): RoleResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$role->id],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        return new RoleResource($this->roles->update($role, $data['name'], $data['permissions']));
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->name === RoleSeeder::SUPER_ADMIN_ROLE) {
            return response()->json(['message' => 'The Super Admin role cannot be deleted.'], 422);
        }

        if ($this->roles->hasUsers($role)) {
            return response()->json(['message' => 'Role is assigned to users and cannot be deleted.'], 422);
        }

        $this->roles->delete($role);

        return response()->json(['message' => 'Role deleted.']);
    }
}
