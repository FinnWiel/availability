<?php

use App\Models\Event;
use App\Models\EventAvailability;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public Event $event;

    public ?CarbonInterface $nextCommonDateTime = null;

    /**
     * @var Collection<int, \App\Models\EventAvailability>
     */
    public Collection $userAvailabilitySlots;

    public string $month = '';

    public function mount(Event $event): void
    {
        abort_unless(
            auth()->user()?->hasRole('admin') ||
                $event
                    ->users()
                    ->whereKey(auth()->id())
                    ->exists(),
            403,
        );

        $this->event = $event->load(['users:id,name,email', 'availabilities.user:id,name,email']);
        $this->userAvailabilitySlots = $this->event->availabilities
            ->where('user_id', auth()->id())
            ->sortBy('available_at')
            ->values();
        $this->nextCommonDateTime = $this->nextCommonDateTime($this->event);

        if ($this->month === '') {
            $this->month = now()->format('Y-m');
        }
    }

    #[On('event-availability-updated')]
    public function refreshAvailabilityData(): void
    {
        $this->event->load(['users:id,name,email', 'availabilities.user:id,name,email']);
        $this->userAvailabilitySlots = $this->event->availabilities
            ->where('user_id', auth()->id())
            ->sortBy('available_at')
            ->values();
        $this->nextCommonDateTime = $this->nextCommonDateTime($this->event);
    }

    public function goToPreviousMonth(): void
    {
        $this->month = $this->resolveMonthStart()->subMonth()->format('Y-m');
    }

    public function goToNextMonth(): void
    {
        $this->month = $this->resolveMonthStart()->addMonth()->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->month = now()->format('Y-m');
    }

    #[Computed]
    public function isCurrentMonth(): bool
    {
        return $this->monthStart->isSameMonth(now());
    }

    #[Computed]
    public function monthStart(): Carbon
    {
        return $this->resolveMonthStart();
    }

    private function resolveMonthStart(): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}$/', $this->month) === 1) {
            return Carbon::createFromFormat('Y-m', $this->month, config('app.timezone'))->startOfMonth();
        }

        return now()->startOfMonth();
    }

    #[Computed]
    public function participantCount(): int
    {
        return $this->event->users->count();
    }

    /**
     * @return Collection<string, int>
     */
    #[Computed]
    public function availabilityCounts(): Collection
    {
        return $this->event->availabilities->groupBy(fn($availability) => $availability->available_at->toDateString())->map(fn($availabilities) => $availabilities->pluck('user_id')->unique()->count());
    }

    /**
     * @return Collection<string, Collection<int, \App\Models\User>>
     */
    #[Computed]
    public function availabilityUsersByDate(): Collection
    {
        return $this->event->availabilities->groupBy(fn($availability) => $availability->available_at->toDateString())->map(function (Collection $availabilities): Collection {
            return $availabilities->map(fn($availability) => $availability->user)->filter()->unique('id')->values();
        });
    }

    /**
     * @return Collection<string, Collection<int, string>>
     */
    #[Computed]
    public function hostNamesByDate(): Collection
    {
        return $this->event->availabilities
            ->filter(fn($availability): bool => $availability->location === 'my-place' && $availability->user !== null)
            ->groupBy(fn($availability) => $availability->available_at->toDateString())
            ->map(function (Collection $availabilities): Collection {
                return $availabilities->map(fn($availability): string => $availability->user->name)->unique()->values();
            });
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function myAvailabilityDates(): Collection
    {
        return $this->userAvailabilitySlots->groupBy(fn($availability) => $availability->available_at->toDateString())->keys();
    }

    /**
     * @return Collection<int, array{date: string, label: string, day: int, inCurrentMonth: bool, attendeeCount: int, hasMyAvailability: bool, isCommonDay: bool, isToday: bool}>
     */
    #[Computed]
    public function calendarDays(): Collection
    {
        $monthStart = $this->monthStart;
        $monthEnd = $monthStart->copy()->endOfMonth();
        $calendarStart = $monthStart->copy()->startOfWeek(CarbonInterface::MONDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(CarbonInterface::SUNDAY);
        $days = collect();

        for ($date = $calendarStart->copy(); $date->lte($calendarEnd); $date = $date->addDay()) {
            $dateString = $date->toDateString();
            $attendeeCount = (int) $this->availabilityCounts->get($dateString, 0);

            $days->push([
                'date' => $dateString,
                'label' => $date->isoFormat('ddd, MMM D'),
                'day' => $date->day,
                'inCurrentMonth' => $date->month === $monthStart->month,
                'attendeeCount' => $attendeeCount,
                'hasMyAvailability' => $this->myAvailabilityDates->contains($dateString),
                'isCommonDay' => $this->participantCount > 0 && $attendeeCount === $this->participantCount,
                'isToday' => $date->isToday(),
            ]);
        }

        return $days;
    }

    private function nextCommonDateTime(Event $event): ?CarbonInterface
    {
        $participantIds = $event->users()->pluck('users.id')->values();

        if ($participantIds->isEmpty()) {
            return null;
        }

        $availabilities = EventAvailability::query()
            ->where('event_id', $event->id)
            ->where('available_at', '>=', now()->startOfDay())
            ->get(['user_id', 'available_at', 'is_all_day']);

        /** @var array<int, array{all_day: array<string, bool>, exact: array<string, bool>}> $availabilityIndex */
        $availabilityIndex = [];

        foreach ($participantIds as $participantId) {
            $availabilityIndex[$participantId] = ['all_day' => [], 'exact' => []];
        }

        foreach ($availabilities as $availability) {
            if (!array_key_exists($availability->user_id, $availabilityIndex)) {
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

                if (!$hasExactSlot && !$hasAllDaySlot) {
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
};
?>

<div class="space-y-4" x-data="{
    openDay(day) {
        $store.calendarDay.day = day;
        window.dispatchEvent(new CustomEvent('calendar-day-selected', { detail: { date: day.date, label: day.label } }));
        $flux.modal('calendar-day-modal').show();
    }
}" x-init="if (!$store.calendarDay) { Alpine.store('calendarDay', { day: { label: '', date: '', attendeeCount: 0, hasMyAvailability: false } }); }">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl">{{ $event->name }}</flux:heading>
            <flux:subheading>{{ __('Select a day to view or plan availability.') }}</flux:subheading>
        </div>

        <flux:modal.trigger name="attendees-modal">
            <flux:button type="button" variant="primary" icon="users" :label="__('View Attendees')"></flux:button>
        </flux:modal.trigger>
    </div>

    <flux:heading size="lg">{{ __('Availability Calendar') }}</flux:heading>
    <flux:card x-cloak>
        <div class="space-y-3">
            <div class="flex items-center justify-between">

                <div class="flex flex-wrap items-center gap-4">
                    <flux:button wire:key="calendar-previous" type="button" variant="ghost" size="sm"
                        icon="chevron-left" wire:click.prevent="goToPreviousMonth" :loading="false"
                        :label="__('Previous month')" />

                    <flux:heading size="md">{{ $this->monthStart->format('F Y') }}</flux:heading>



                    <flux:button wire:key="calendar-next" type="button" variant="ghost" size="sm"
                        icon="chevron-right" wire:click.prevent="goToNextMonth" :loading="false"
                        :label="__('Next month')" />
                </div>
                <flux:button wire:key="calendar-today" type="button"
                    :variant="$this->isCurrentMonth ? 'ghost' : 'primary'" :disabled="$this->isCurrentMonth"
                    size="sm" icon:trailing="calendar" wire:click.prevent="goToToday" :loading="false"
                    :label="__('Go to current month')">{{ __('Today') }}</flux:button>
            </div>

            <div class="grid grid-cols-7 gap-2 text-center text-sm text-zinc-500 dark:text-zinc-400">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                    <div class="py-1 font-medium">{{ $weekday }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-2">
                @foreach ($this->calendarDays as $day)
                    @php
                        $availableUsers = $this->availabilityUsersByDate->get($day['date'], collect());
                        $hostNames = $this->hostNamesByDate->get($day['date'], collect());
                    @endphp

                    <flux:modal.trigger name="calendar-day-modal">
                        <x-events.day-card :day="$day" :available-users="$availableUsers" :host-names="$hostNames"
                            x-on:click="openDay({
                                date: {{ \Illuminate\Support\Js::from($day['date']) }},
                                label: {{ \Illuminate\Support\Js::from($day['label']) }},
                                attendeeCount: {{ $day['attendeeCount'] }},
                                participantCount: {{ $this->participantCount }},
                                hasMyAvailability: {{ $day['hasMyAvailability'] ? 'true' : 'false' }}
                            })" />
                    </flux:modal.trigger>
                @endforeach
            </div>
        </div>
    </flux:card>

    <livewire:events.calendar-day-modal :event="$event" :key="'calendar-day-modal-' . $event->id . '-' . $event->availabilities->count()" />
    <livewire:events.attendees-modal :event="$event" :key="'attendees-modal-' . $event->id . '-' . $event->users->count()" />
</div>
