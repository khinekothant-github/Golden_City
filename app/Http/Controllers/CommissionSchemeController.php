<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommissionSchemeResource;
use App\Models\CommissionScheme;
use App\Repositories\CommissionSchemeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommissionSchemeController extends Controller
{
    public function __construct(private readonly CommissionSchemeRepository $commissionSchemes) {}

    public function index(): AnonymousResourceCollection
    {
        return CommissionSchemeResource::collection($this->commissionSchemes->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        return (new CommissionSchemeResource($this->commissionSchemes->create($data)))->response()->setStatusCode(201);
    }

    public function show(CommissionScheme $commissionScheme): CommissionSchemeResource
    {
        return new CommissionSchemeResource($this->commissionSchemes->find($commissionScheme->id));
    }

    public function update(Request $request, CommissionScheme $commissionScheme): CommissionSchemeResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        return new CommissionSchemeResource($this->commissionSchemes->update($commissionScheme, $data));
    }

    public function destroy(CommissionScheme $commissionScheme): JsonResponse
    {
        $this->commissionSchemes->delete($commissionScheme);

        return response()->json(['message' => 'Commission scheme deleted.']);
    }
}