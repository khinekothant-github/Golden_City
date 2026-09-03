<?php

namespace App\Http\Controllers;

use App\Http\Resources\BuildingResource;
use App\Models\Building;
use App\Repositories\BuildingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class BuildingController extends Controller
{
    public function __construct(private readonly BuildingRepository $buildings) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $phaseId = $request->integer('phase_id') ?: null;

        return BuildingResource::collection($this->buildings->paginate($phaseId));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phase_id' => ['required', 'integer', Rule::exists('phases', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255', $this->phaseScopedUnique($request)],
            'building_type' => ['nullable', 'string', 'max:255'],
        ]);

        return (new BuildingResource($this->buildings->create($data)))->response()->setStatusCode(201);
    }

    public function show(Building $building): BuildingResource
    {
        return new BuildingResource($this->buildings->find($building->id));
    }

    public function update(Request $request, Building $building): BuildingResource
    {
        $data = $request->validate([
            'phase_id' => ['sometimes', 'integer', Rule::exists('phases', 'id')->whereNull('deleted_at')],
            'name' => ['sometimes', 'string', 'max:255', $this->phaseScopedUnique($request, $building)],
            'building_type' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        return new BuildingResource($this->buildings->update($building, $data));
    }

    public function destroy(Building $building): JsonResponse
    {
        if ($this->buildings->hasUnits($building)) {
            return response()->json(['message' => 'Building has units and cannot be deleted.'], 422);
        }

        $this->buildings->delete($building);

        return response()->json(['message' => 'Building deleted.']);
    }

    private function phaseScopedUnique(Request $request, ?Building $building = null): Unique
    {
        $rule = Rule::unique('buildings', 'name')
            ->where(fn ($query) => $query->where('phase_id', $request->input('phase_id')));

        if ($building !== null) {
            $rule->ignore($building->id);
        }

        return $rule;
    }
}
