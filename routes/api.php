<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        $user = $request->user();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    });

    Route::middleware('permission:read_users')->get('/users', [UserController::class, 'index']);
    Route::middleware('permission:create_users')->post('/users', [UserController::class, 'store']);
    Route::middleware('permission:read_users')->get('/users/{user}', [UserController::class, 'show']);
    Route::middleware('permission:update_users')->put('/users/{user}', [UserController::class, 'update']);
    Route::middleware('permission:delete_users')->delete('/users/{user}', [UserController::class, 'destroy']);

    Route::middleware('permission:read_roles')->get('/roles', [RoleController::class, 'index']);
    Route::middleware('permission:create_roles')->post('/roles', [RoleController::class, 'store']);
    Route::middleware('permission:read_roles')->get('/roles/{role}', [RoleController::class, 'show']);
    Route::middleware('permission:update_roles')->put('/roles/{role}', [RoleController::class, 'update']);
    Route::middleware('permission:delete_roles')->delete('/roles/{role}', [RoleController::class, 'destroy']);

    Route::middleware('permission:read_roles')->get('/permissions', [PermissionController::class, 'index']);
});
