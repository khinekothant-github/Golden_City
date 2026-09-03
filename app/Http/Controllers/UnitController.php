<?php

namespace App\Http\Controllers;

use App\Enums\UnitStatus;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Repositories\UnitRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UnitController extends Controller
{
    public function __construct(private readonly UnitRepository $units) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return UnitResource::collection(
            $this->units->paginate($request->integer('phase_id') ?: null, $request->integer('building_id') ?: null)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules($request));

        return (new UnitResource($this->units->create($data)))->response()->setStatusCode(201);
    }

    public function show(Unit $unit): UnitResource
    {
        return new UnitResource($this->units->find($unit->id));
    }

    public function update(Request $request, Unit $unit): UnitResource
    {
        $data = $request->validate($this->rules($request, $unit));

        return new UnitResource($this->units->update($unit, $data));
    }

    public function destroy(Unit $unit): JsonResponse
    {
        if ($this->units->hasSales($unit)) {
            return response()->json(['message' => 'Unit has sales history and cannot be deleted.'], 422);
        }

        $this->units->delete($unit);

        return response()->json(['message' => 'Unit deleted.']);
    }

    /**
     * A unit must belong to a building or hang directly off a phase — never both, never neither.
     *
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?Unit $unit = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', $this->parentScopedUnique($request, $unit)],
            'building_id' => [
                'nullable',
                'integer',
                'prohibits:phase_id',
                Rule::exists('buildings', 'id')->whereNull('deleted_at'),
            ],
            'phase_id' => [
                Rule::requiredIf(fn () => ! $request->filled('building_id')),
                'nullable',
                'integer',
                'prohibits:building_id',
                Rule::exists('phases', 'id')->whereNull('deleted_at'),
            ],
            'type' => ['nullable', 'string', 'max:255'],
            'room_description' => ['nullable', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::enum(UnitStatus::class)],
        ];
    }

    private function parentScopedUnique(Request $request, ?Unit $unit = null): Unique
    {
        $parentKey = $request->filled('building_id') ? 'building_id' : 'phase_id';
        $parentValue = $request->input($parentKey);

        $rule = Rule::unique('units', 'name')
            ->where(fn ($query) => $query->where($parentKey, $parentValue));

        if ($unit !== null) {
            $rule->ignore($unit->id);
        }

        return $rule;
    }
}
