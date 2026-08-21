<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly UserRepository $users) {}

    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection($this->users->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ]);

        $user = $this->users->create($data);

        if (isset($data['role'])) {
            $user->syncRoles($data['role']);
        }

        return (new UserResource($this->users->find($user->id)))->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($this->users->find($user->id));
    }

    public function update(Request $request, User $user): UserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ]);

        if (isset($data['role'])) {
            $user->syncRoles($data['role']);
        }

        unset($data['role']);

        return new UserResource($this->users->update($user, $data));
    }

    public function destroy(User $user): JsonResponse
    {
        $this->users->delete($user);

        return response()->json(['message' => 'User deleted.']);
    }
}
