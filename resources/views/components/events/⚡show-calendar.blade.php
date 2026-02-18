<?php

use App\Models\Event;
use App\Models\EventAvailability;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Event $event;

    public ?CarbonInterface $nextCommonDateTime = null;

    /**
     * @var Collection<int, \App\Models\EventAvailability>
     */
    public Collection $userAvailabilitySlots;

    public string $month = '';

    public ?string $selectedDate = null;

    public ?string $selectedDayLabel = null;

    public string $selectedTime = 'all-day';

    public bool $selectedAtMyPlace = false;

    public function mount(Event $event, ?CarbonInterface $nextCommonDateTime, Collection $userAvailabilitySlots): void
    {
        $this->event = $event;
        $this->nextCommonDateTime = $nextCommonDateTime;
        $this->userAvailabilitySlots = $userAvailabilitySlots;

        if ($this->month === '') {
            $this->month = now()->format('Y-m');
        }
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

    public function setSelectedDay(string $date, string $label): void
    {
        $this->selectedDate = $date;
        $this->selectedDayLabel = $label;
        $this->selectedTime = 'all-day';

        $this->selectedAtMyPlace = $this->userAvailabilitySlots->first(fn ($availability) => $availability->available_at->toDateString() === $date)?->location === 'my-place';
    }

    public function addAvailability(): void
    {
        if ($this->selectedDate === null) {
            return;
        }

        $currentUser = request()->user();

        if ($currentUser === null) {
            return;
        }

        $isAllDay = $this->selectedTime === 'all-day';
        $availableAt = $isAllDay ? Carbon::createFromFormat('Y-m-d', $this->selectedDate, config('app.timezone'))->startOfDay() : Carbon::createFromFormat('Y-m-d H:i', $this->selectedDate.' '.$this->selectedTime, config('app.timezone'))->seconds(0);
        $location = $this->selectedAtMyPlace ? 'my-place' : null;

        $availability = EventAvailability::query()->firstOrCreate(
            [
                'event_id' => $this->event->id,
                'user_id' => $currentUser->id,
                'available_at' => $availableAt,
            ],
            [
                'is_all_day' => $isAllDay,
                'location' => $location,
            ],
        );

        if ($availability->is_all_day !== $isAllDay || $availability->location !== $location) {
            $availability->update([
                'is_all_day' => $isAllDay,
                'location' => $location,
            ]);
        }

        $this->event->load(['users:id,name,email', 'availabilities.user:id,name,email']);
        $this->userAvailabilitySlots = $this->event->availabilities->where('user_id', $currentUser->id)->sortBy('available_at')->values();
    }

    public function saveLocation(): void
    {
        if ($this->selectedDate === null) {
            return;
        }

        $currentUser = request()->user();

        if ($currentUser === null) {
            return;
        }

        EventAvailability::query()
            ->where('event_id', $this->event->id)
            ->where('user_id', $currentUser->id)
            ->whereDate('available_at', $this->selectedDate)
            ->update([
                'location' => $this->selectedAtMyPlace ? 'my-place' : null,
            ]);

        $this->event->load(['users:id,name,email', 'availabilities.user:id,name,email']);
        $this->userAvailabilitySlots = $this->event->availabilities->where('user_id', $currentUser->id)->sortBy('available_at')->values();
    }

    public function removeAvailability(): void
    {
        if ($this->selectedDate === null) {
            return;
        }

        $currentUser = request()->user();

        if ($currentUser === null) {
            return;
        }

        EventAvailability::query()->where('event_id', $this->event->id)->where('user_id', $currentUser->id)->whereDate('available_at', $this->selectedDate)->delete();

        $this->selectedAtMyPlace = false;

        $this->event->load(['users:id,name,email', 'availabilities.user:id,name,email']);
        $this->userAvailabilitySlots = $this->event->availabilities->where('user_id', $currentUser->id)->sortBy('available_at')->values();
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
        return $this->event->availabilities->groupBy(fn ($availability) => $availability->available_at->toDateString())->map(fn ($availabilities) => $availabilities->pluck('user_id')->unique()->count());
    }

    /**
     * @return Collection<string, Collection<int, \App\Models\User>>
     */
    #[Computed]
    public function availabilityUsersByDate(): Collection
    {
        return $this->event->availabilities->groupBy(fn ($availability) => $availability->available_at->toDateString())->map(function (Collection $availabilities): Collection {
            return $availabilities->map(fn ($availability) => $availability->user)->filter()->unique('id')->values();
        });
    }

    /**
     * @return Collection<string, Collection<int, string>>
     */
    #[Computed]
    public function hostNamesByDate(): Collection
    {
        return $this->event->availabilities
            ->filter(fn ($availability): bool => $availability->location === 'my-place' && $availability->user !== null)
            ->groupBy(fn ($availability) => $availability->available_at->toDateString())
            ->map(function (Collection $availabilities): Collection {
                return $availabilities->map(fn ($availability): string => $availability->user->name)->unique()->values();
            });
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function myAvailabilityDates(): Collection
    {
        return $this->userAvailabilitySlots->groupBy(fn ($availability) => $availability->available_at->toDateString())->keys();
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

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function timeOptions(): Collection
    {
        return collect(['all-day'])->concat(
            collect(range(0, 47))->map(function (int $index): string {
                $hours = intdiv($index, 2);
                $minutes = $index % 2 === 0 ? '00' : '30';

                return sprintf('%02d:%s', $hours, $minutes);
            }),
        );
    }

    #[Computed]
    public function selectedDayHasMyAvailability(): bool
    {
        if ($this->selectedDate === null) {
            return false;
        }

        return $this->myAvailabilityDates->contains($this->selectedDate);
    }

    /**
     * @return Collection<int, array{name: string, location: string}>
     */
    #[Computed]
    public function selectedDayLocations(): Collection
    {
        if ($this->selectedDate === null) {
            return collect();
        }

        return $this->event->availabilities
            ->filter(function ($availability): bool {
                return $availability->available_at->toDateString() === $this->selectedDate && filled($availability->location) && $availability->user !== null;
            })
            ->groupBy('user_id')
            ->map(function (Collection $availabilities): array {
                $availability = $availabilities->first();

                return [
                    'name' => $availability->user->name,
                    'location' => $availability->location === 'my-place' ? __('At their place') : __('Location provided'),
                ];
            })
            ->values();
    }
};
?>

<div class="space-y-4" x-data="{
    openDay(day) {
        $store.calendarDay.day = day;
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

    <flux:card>
        <div class="space-y-3">
            <flux:heading size="lg">{{ __('Availability Calendar') }}</flux:heading>

            <div class="flex flex-wrap items-center justify-between">
                <flux:heading size="lg">{{ $this->monthStart->format('F Y') }}</flux:heading>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button wire:key="calendar-today" type="button"
                        :variant="$this->isCurrentMonth ? 'ghost' : 'primary'" :disabled="$this->isCurrentMonth"
                        size="sm" icon="calendar-days" wire:click.prevent="goToToday" :loading="false"
                        :label="__('Go to current month')" />

                    <flux:button wire:key="calendar-previous" type="button" variant="filled" size="sm"
                        icon="chevron-left" wire:click.prevent="goToPreviousMonth" :loading="false"
                        :label="__('Previous month')" />

                    <flux:button wire:key="calendar-next" type="button" variant="filled" size="sm"
                        icon="chevron-right" wire:click.prevent="goToNextMonth" :loading="false"
                        :label="__('Next month')" />
                </div>
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

                    <button type="button" wire:click="setSelectedDay('{{ $day['date'] }}', '{{ $day['label'] }}')"
                        @if ($day['isCommonDay']) style="border-color: var(--color-accent); border-width: 2px; background-color: color-mix(in srgb, var(--color-accent) 10%, transparent);" @endif
                        class="h-full aspect-square rounded-lg border p-2 text-left transition hover:border-zinc-400 hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/60 {{ $day['inCurrentMonth'] ? 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/50' : 'border-zinc-100 bg-zinc-50/70 text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900/20 dark:text-zinc-500' }} {{ $day['isCommonDay'] ? 'border-2 border-accent bg-accent/10 dark:border-accent dark:bg-accent/10' : '' }} {{ $day['isToday'] ? 'ring-2 ring-white' : '' }}"
                        x-on:click="openDay({
                            date: {{ \Illuminate\Support\Js::from($day['date']) }},
                            label: {{ \Illuminate\Support\Js::from($day['label']) }},
                            attendeeCount: {{ $day['attendeeCount'] }},
                            participantCount: {{ $this->participantCount }},
                            hasMyAvailability: {{ $day['hasMyAvailability'] ? 'true' : 'false' }}
                        })">
                        <div class="flex h-full flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="flex flex-col items-start justify-between">
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ trans_choice('{1} :count attendee|[2,*] :count attendees', $day['attendeeCount'], ['count' => $day['attendeeCount']]) }}
                                    </div>
                                    @if ($day['hasMyAvailability'])
                                        <div class="text-xs font-medium text-accent">
                                            {{ __('You are available') }}
                                        </div>
                                    @endif

                                </div>

                                @if ($hostNames->isNotEmpty())
                                    <flux:tooltip position="bottom"
                                        content="{{ $hostNames->implode(', ') }} {{ __('can host at their place') }}">
                                        <span class="text-zinc-500 dark:text-zinc-400"
                                            data-test="availability-host-pin-{{ $day['date'] }}">
                                            <flux:icon.map-pin class="size-5" />
                                        </span>
                                    </flux:tooltip>
                                @elseif ($day['isCommonDay'])
                                    <flux:tooltip position="bottom" content="{{ __('No host location set yet') }}">
                                        <span class="text-zinc-400 dark:text-zinc-500"
                                            data-test="availability-host-pin-off-{{ $day['date'] }}">
                                            <flux:icon.map-pin-off class="size-5" />
                                        </span>
                                    </flux:tooltip>
                                @endif


                            </div>

                            <div class="flex items-end justify-between gap-2">
                                @if ($availableUsers->isNotEmpty())
                                    <div class="flex -space-x-1" data-test="availability-avatars-{{ $day['date'] }}">
                                        @foreach ($availableUsers->take(3) as $availableUser)
                                            @php
                                                $availableUserAvatar =
                                                    $availableUser->profile_photo_url ??
                                                    ($availableUser->avatar_url ?? null);
                                            @endphp
                                            <div
                                                data-test="availability-avatar-{{ $day['date'] }}-user-{{ $availableUser->id }}">
                                                <flux:avatar circle size="sm" :name="$availableUser->name"
                                                    :src="$availableUserAvatar" />
                                            </div>
                                        @endforeach

                                        @if ($availableUsers->count() > 3)
                                            <div
                                                class="flex size-6 items-center justify-center rounded-full bg-zinc-200 text-[10px] font-semibold text-zinc-700 ring-2 ring-white dark:bg-zinc-700 dark:text-zinc-200 dark:ring-zinc-900">
                                                +{{ $availableUsers->count() - 3 }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div></div>
                                @endif

                                <div
                                    class="text-right text-lg font-semibold leading-none {{ $day['isCommonDay'] ? 'text-accent' : ($day['inCurrentMonth'] ? 'text-zinc-900 dark:text-zinc-100' : '') }}">
                                    {{ $day['day'] }}
                                </div>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    </flux:card>

    <flux:modal name="calendar-day-modal" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg" x-text="$store.calendarDay.day.label"></flux:heading>
                <flux:text class="mt-1" x-text="$store.calendarDay.day.date"></flux:text>
            </div>

            <div
                class="rounded-lg border border-zinc-200 p-3 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <div x-text="`${$store.calendarDay.day.attendeeCount ?? 0} attendee(s) currently marked available.`">
                </div>
                <div class="mt-1" x-show="$store.calendarDay.day.hasMyAvailability">
                    {{ __('You already marked availability for this day.') }}
                </div>
                <div class="mt-1" x-show="!$store.calendarDay.day.hasMyAvailability">
                    {{ __('You have not marked your availability for this day yet.') }}
                </div>
            </div>

            @if ($this->selectedDayLocations->isNotEmpty())
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:heading size="sm">{{ __('Available locations') }}</flux:heading>

                    <div class="mt-2 space-y-1 text-sm">
                        @foreach ($this->selectedDayLocations as $locationEntry)
                            <div class="text-zinc-700 dark:text-zinc-200">
                                <span class="font-medium">{{ $locationEntry['name'] }}:</span>
                                <span>{{ $locationEntry['location'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <flux:field>
                <flux:label>{{ __('Time') }}</flux:label>
                <flux:select wire:model="selectedTime">
                    @foreach ($this->timeOptions as $timeOption)
                        <option value="{{ $timeOption }}">
                            {{ $timeOption === 'all-day' ? __('All day') : $timeOption }}
                        </option>
                    @endforeach
                </flux:select>
            </flux:field>

            @if ($this->selectedDayHasMyAvailability)
                <flux:field>
                    <flux:label>{{ __('Location preference') }}</flux:label>
                    <flux:checkbox wire:model="selectedAtMyPlace" wire:change="saveLocation"
                        :label="__('I can host at my place')" />
                </flux:field>
            @endif

            @if ($this->selectedDayHasMyAvailability)
                <div class="flex flex-wrap gap-2">
                    <flux:button type="button" variant="danger" wire:click="removeAvailability" :loading="false"
                        icon="trash" :label="__('Remove availability')">
                        {{ __('Remove Availability') }}
                    </flux:button>
                </div>
            @else
                <flux:button type="button" variant="primary" wire:click="addAvailability" :loading="false"
                    icon="plus-circle" :label="__('Add availability')">
                    {{ __('Add Availability') }}
                </flux:button>
            @endif

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Close') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="attendees-modal" class="md:w-[30rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Attendees') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Everyone assigned to this event.') }}</flux:text>
            </div>

            <div class="space-y-2">
                @foreach ($event->users as $user)
                    @php
                        $avatarUrl = $user->profile_photo_url ?? ($user->avatar_url ?? null);
                    @endphp

                    <div
                        class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                        <flux:avatar size="sm" :name="$user->name" :src="$avatarUrl" />

                        <div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</div>
                            <div class="text-zinc-500 dark:text-zinc-400">{{ $user->email }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </flux:modal>
</div>
