<?php

namespace App\Http\Controllers;

use App\Repositories\PermissionRepository;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionRepository $permissions) {}

    public function index(): JsonResponse
    {
        return response()->json($this->permissions->all());
    }
}
