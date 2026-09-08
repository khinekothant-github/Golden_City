<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CommissionSchemeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerVisitController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UnitController;
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

    Route::middleware('permission:read_phases')->get('/phases', [PhaseController::class, 'index']);
    Route::middleware('permission:create_phases')->post('/phases', [PhaseController::class, 'store']);
    Route::middleware('permission:read_phases')->get('/phases/{phase}', [PhaseController::class, 'show']);
    Route::middleware('permission:update_phases')->put('/phases/{phase}', [PhaseController::class, 'update']);
    Route::middleware('permission:delete_phases')->delete('/phases/{phase}', [PhaseController::class, 'destroy']);

    Route::middleware('permission:read_buildings')->get('/buildings', [BuildingController::class, 'index']);
    Route::middleware('permission:create_buildings')->post('/buildings', [BuildingController::class, 'store']);
    Route::middleware('permission:read_buildings')->get('/buildings/{building}', [BuildingController::class, 'show']);
    Route::middleware('permission:update_buildings')->put('/buildings/{building}', [BuildingController::class, 'update']);
    Route::middleware('permission:delete_buildings')->delete('/buildings/{building}', [BuildingController::class, 'destroy']);

    Route::middleware('permission:read_units')->get('/units', [UnitController::class, 'index']);
    Route::middleware('permission:create_units')->post('/units', [UnitController::class, 'store']);
    Route::middleware('permission:read_units')->get('/units/{unit}', [UnitController::class, 'show']);
    Route::middleware('permission:update_units')->put('/units/{unit}', [UnitController::class, 'update']);
    Route::middleware('permission:delete_units')->delete('/units/{unit}', [UnitController::class, 'destroy']);

    Route::middleware('permission:read_agencies')->get('/agencies', [AgencyController::class, 'index']);
    Route::middleware('permission:create_agencies')->post('/agencies', [AgencyController::class, 'store']);
    Route::middleware('permission:read_agencies')->get('/agencies/{agency}', [AgencyController::class, 'show']);
    Route::middleware('permission:update_agencies')->put('/agencies/{agency}', [AgencyController::class, 'update']);
    Route::middleware('permission:delete_agencies')->delete('/agencies/{agency}', [AgencyController::class, 'destroy']);

    Route::middleware('permission:read_commission_schemes')->get('/commission-schemes', [CommissionSchemeController::class, 'index']);
    Route::middleware('permission:create_commission_schemes')->post('/commission-schemes', [CommissionSchemeController::class, 'store']);
    Route::middleware('permission:read_commission_schemes')->get('/commission-schemes/{commissionScheme}', [CommissionSchemeController::class, 'show']);
    Route::middleware('permission:update_commission_schemes')->put('/commission-schemes/{commissionScheme}', [CommissionSchemeController::class, 'update']);
    Route::middleware('permission:delete_commission_schemes')->delete('/commission-schemes/{commissionScheme}', [CommissionSchemeController::class, 'destroy']);

    Route::middleware('permission:read_events')->get('/events', [EventController::class, 'index']);
    Route::middleware('permission:create_events')->post('/events', [EventController::class, 'store']);
    Route::middleware('permission:read_events')->get('/events/{event}', [EventController::class, 'show']);
    Route::middleware('permission:update_events')->put('/events/{event}', [EventController::class, 'update']);
    Route::middleware('permission:delete_events')->delete('/events/{event}', [EventController::class, 'destroy']);

    Route::middleware('permission:read_customers')->get('/customers', [CustomerController::class, 'index']);
    Route::middleware('permission:create_customers')->post('/customers', [CustomerController::class, 'store']);
    Route::middleware('permission:read_customers')->get('/customers/{customer}', [CustomerController::class, 'show']);
    Route::middleware('permission:update_customers')->put('/customers/{customer}', [CustomerController::class, 'update']);
    Route::middleware('permission:delete_customers')->delete('/customers/{customer}', [CustomerController::class, 'destroy']);

    Route::middleware('permission:record_customer_visits')->post('/customers/{customer}/visits', [CustomerVisitController::class, 'store']);
});
