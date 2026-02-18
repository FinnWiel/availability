<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\DeleteManagedEventRequest;
use App\Http\Requests\Event\StoreManagedEventRequest;
use App\Http\Requests\Event\UpdateManagedEventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class EventManagementController extends Controller
{
    public function store(StoreManagedEventRequest $request): RedirectResponse
    {
        $event = Event::query()->create([
            'name' => $request->string('name')->toString(),
            'color' => strtoupper($request->string('color')->toString()),
            'created_by' => $request->user()?->id,
        ]);

        $event->users()->sync($this->assignedUserIds($request->input('users', []), $request->user()?->id));

        return to_route('events.index')
            ->with('status', 'Event created successfully.');
    }

    public function update(UpdateManagedEventRequest $request, Event $event): RedirectResponse
    {
        $event->update([
            'name' => $request->string('name')->toString(),
            'color' => strtoupper($request->string('color')->toString()),
        ]);

        $event->users()->sync($this->assignedUserIds($request->input('users', []), $request->user()?->id));

        return to_route('events.index')
            ->with('status', 'Event updated successfully.');
    }

    public function destroy(DeleteManagedEventRequest $request, Event $event): RedirectResponse
    {
        $event->delete();

        return to_route('events.index')
            ->with('status', 'Event deleted successfully.');
    }

    /**
     * @param  array<int, mixed>  $selectedUsers
     * @return Collection<int, int>
     */
    private function assignedUserIds(array $selectedUsers, ?int $currentUserId): Collection
    {
        return collect($selectedUsers)
            ->when($currentUserId !== null, fn (Collection $ids): Collection => $ids->push($currentUserId))
            ->filter(fn (mixed $id): bool => is_int($id) || ctype_digit((string) $id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }
}
