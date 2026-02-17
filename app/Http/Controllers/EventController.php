<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\StoreEventAvailabilityRequest;
use App\Models\Event;
use App\Models\EventAvailability;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class EventController extends Controller
{
    public function show(Event $event): View
    {
        $this->ensureUserCanAccessEvent($event);

        $event->load(['users:id,name,email', 'availabilities.user:id,name,email']);

        $userAvailabilitySlots = $event->availabilities
            ->where('user_id', request()->user()?->id)
            ->sortBy('available_at')
            ->values();

        $nextCommonDateTime = $this->nextCommonDateTime($event);

        return view('events.show', [
            'event' => $event,
            'nextCommonDateTime' => $nextCommonDateTime,
            'timeOptions' => $this->timeOptions(),
            'userAvailabilitySlots' => $userAvailabilitySlots,
        ]);
    }

    public function storeAvailability(StoreEventAvailabilityRequest $request, Event $event): RedirectResponse
    {
        $this->ensureUserCanAccessEvent($event);

        $isAllDay = $request->string('time')->toString() === 'all-day';

        $availableAt = $isAllDay
            ? Carbon::createFromFormat('Y-m-d', $request->string('date')->toString(), config('app.timezone'))->startOfDay()
            : Carbon::createFromFormat(
                'Y-m-d H:i',
                $request->string('date')->toString().' '.$request->string('time')->toString(),
                config('app.timezone'),
            )->seconds(0);

        EventAvailability::query()->firstOrCreate([
            'event_id' => $event->id,
            'user_id' => $request->user()->id,
            'available_at' => $availableAt,
            'is_all_day' => $isAllDay,
        ]);

        return to_route('events.show', $event)
            ->with('status', 'Availability added successfully.');
    }

    public function destroyAvailability(Event $event, EventAvailability $availability): RedirectResponse
    {
        $this->ensureUserCanAccessEvent($event);

        abort_if($availability->event_id !== $event->id, 404);

        $isAdmin = request()->user()?->hasRole('admin') ?? false;
        $isOwner = $availability->user_id === request()->user()?->id;

        abort_unless($isAdmin || $isOwner, 403);

        $availability->delete();

        return to_route('events.show', $event)
            ->with('status', 'Availability removed successfully.');
    }

    private function ensureUserCanAccessEvent(Event $event): void
    {
        abort_unless(
            request()->user()?->hasRole('admin') || $event->users()->whereKey(request()->user()?->id)->exists(),
            403,
        );
    }

    private function nextCommonDateTime(Event $event): ?CarbonInterface
    {
        $participantIds = $event->users()->pluck('users.id')->values();
        $participantCount = $participantIds->count();

        if ($participantCount === 0) {
            return null;
        }

        $availabilities = EventAvailability::query()
            ->where('event_id', $event->id)
            ->where('available_at', '>=', now()->startOfDay())
            ->get(['user_id', 'available_at', 'is_all_day']);

        /** @var array<int, array{all_day: array<string, bool>, exact: array<string, bool>}> $availabilityIndex */
        $availabilityIndex = [];

        foreach ($participantIds as $participantId) {
            $availabilityIndex[$participantId] = [
                'all_day' => [],
                'exact' => [],
            ];
        }

        foreach ($availabilities as $availability) {
            if (! array_key_exists($availability->user_id, $availabilityIndex)) {
                continue;
            }

            if ($availability->is_all_day) {
                $availabilityIndex[$availability->user_id]['all_day'][$availability->available_at->toDateString()] = true;

                continue;
            }

            $availabilityIndex[$availability->user_id]['exact'][$availability->available_at->format('Y-m-d H:i')] = true;
        }

        $candidate = now()->seconds(0);

        if ((int) $candidate->format('i') % 30 !== 0) {
            $candidate = $candidate->addMinutes(30 - ((int) $candidate->format('i') % 30));
        }

        for ($index = 0; $index < 48 * 180; $index++) {
            $candidateDate = $candidate->toDateString();
            $candidateDateTime = $candidate->format('Y-m-d H:i');

            $everyoneAvailable = true;

            foreach ($participantIds as $participantId) {
                $hasExactSlot = $availabilityIndex[$participantId]['exact'][$candidateDateTime] ?? false;
                $hasAllDaySlot = $availabilityIndex[$participantId]['all_day'][$candidateDate] ?? false;

                if (! $hasExactSlot && ! $hasAllDaySlot) {
                    $everyoneAvailable = false;

                    break;
                }
            }

            if ($everyoneAvailable) {
                return $candidate->copy();
            }

            $candidate = $candidate->addMinutes(30);
        }

        return null;
    }

    /**
     * @return Collection<int, string>
     */
    private function timeOptions(): Collection
    {
        return collect(['all-day'])
            ->concat(collect(range(0, 47))
                ->map(function (int $index): string {
                    $hours = intdiv($index, 2);
                    $minutes = $index % 2 === 0 ? '00' : '30';

                    return sprintf('%02d:%s', $hours, $minutes);
                }));
    }
}
