<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Pagination\LengthAwarePaginator;

class EventRepository
{
    public function paginate(): LengthAwarePaginator
    {
        return Event::orderBy('starts_at')->paginate();
    }

    public function find(int $id): ?Event
    {
        return Event::find($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Event
    {
        return Event::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Event $event, array $data): Event
    {
        $event->update($data);

        return $event;
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }
}