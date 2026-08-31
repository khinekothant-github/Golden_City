<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgencyResource;
use App\Models\Agency;
use App\Repositories\AgencyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AgencyController extends Controller
{
    public function __construct(private readonly AgencyRepository $agencies) {}

    public function index(): AnonymousResourceCollection
    {
        return AgencyResource::collection($this->agencies->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'staff' => ['nullable', 'string', 'max:255'],
            'commission_id' => ['nullable', 'integer', Rule::exists('commission_schemes', 'id')],
            'phone' => ['nullable', 'string', 'max:255'],
            'sale_staff_phone' => ['nullable', 'string', 'max:255'],
        ]);

        return (new AgencyResource($this->agencies->create($data)))->response()->setStatusCode(201);
    }

    public function show(Agency $agency): AgencyResource
    {
        return new AgencyResource($this->agencies->find($agency->id));
    }

    public function update(Request $request, Agency $agency): AgencyResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'staff' => ['sometimes', 'nullable', 'string', 'max:255'],
            'commission_id' => ['sometimes', 'nullable', 'integer', Rule::exists('commission_schemes', 'id')],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sale_staff_phone' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        return new AgencyResource($this->agencies->update($agency, $data));
    }

    public function destroy(Agency $agency): JsonResponse
    {
        $this->agencies->delete($agency);

        return response()->json(['message' => 'Agency deleted.']);
    }
}