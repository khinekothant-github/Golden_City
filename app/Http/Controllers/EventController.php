<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Repositories\EventRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function __construct(private readonly EventRepository $events) {}

    public function index(): AnonymousResourceCollection
    {
        return EventResource::collection($this->events->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        return (new EventResource($this->events->create($data)))->response()->setStatusCode(201);
    }

    public function show(Event $event): EventResource
    {
        return new EventResource($this->events->find($event->id));
    }

    public function update(Request $request, Event $event): EventResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        return new EventResource($this->events->update($event, $data));
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->events->delete($event);

        return response()->json(['message' => 'Event deleted.']);
    }
}