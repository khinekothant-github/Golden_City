<?php

namespace App\Http\Controllers;

use App\Http\Resources\PhaseResource;
use App\Models\Phase;
use App\Repositories\PhaseRepository;
use App\Enums\PhaseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PhaseController extends Controller
{
    public function __construct(private readonly PhaseRepository $phases) {}

    public function index(): AnonymousResourceCollection
    {
        return PhaseResource::collection($this->phases->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(PhaseType::class)],
        ]);

        return (new PhaseResource($this->phases->create($data)))->response()->setStatusCode(201);
    }

    public function show(Phase $phase): PhaseResource
    {
        return new PhaseResource($this->phases->find($phase->id));
    }

    public function update(Request $request, Phase $phase): PhaseResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(PhaseType::class)],
        ]);

        return new PhaseResource($this->phases->update($phase, $data));
    }

    public function destroy(Phase $phase): JsonResponse
    {
        if ($this->phases->hasChildren($phase)) {
            return response()->json(['message' => 'Phase has buildings or units and cannot be deleted.'], 422);
        }

        $this->phases->delete($phase);

        return response()->json(['message' => 'Phase deleted.']);
    }
}