<?php

use App\Models\Event;
use App\Models\EventAvailability;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Event $event;

    public ?string $selectedDate = null;

    public ?string $selectedDayLabel = null;

    public string $selectedTime = 'all-day';

    public bool $selectedAtMyPlace = false;

    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    public function setSelectedDay(string $date, string $label): void
    {
        $this->selectedDate = $date;
        $this->selectedDayLabel = $label;
        $this->selectedTime = 'all-day';

        $this->selectedAtMyPlace = $this->event->availabilities
            ->where('user_id', auth()->id())
            ->first(fn ($availability) => $availability->available_at->toDateString() === $date)?->location === 'my-place';

        $this->dispatch('calendar-day-synced', date: $date);
    }

    public function addAvailability(): void
    {
        if ($this->selectedDate === null || auth()->user() === null) {
            return;
        }

        $isAllDay = $this->selectedTime === 'all-day';
        $availableAt = $isAllDay
            ? Carbon::createFromFormat('Y-m-d', $this->selectedDate, config('app.timezone'))->startOfDay()
            : Carbon::createFromFormat('Y-m-d H:i', $this->selectedDate.' '.$this->selectedTime, config('app.timezone'))->seconds(0);
        $location = $this->selectedAtMyPlace ? 'my-place' : null;

        $availability = EventAvailability::query()->firstOrCreate(
            [
                'event_id' => $this->event->id,
                'user_id' => auth()->id(),
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
        $this->dispatch('event-availability-updated');
    }

    public function saveLocation(): void
    {
        if ($this->selectedDate === null || auth()->user() === null) {
            return;
        }

        EventAvailability::query()
            ->where('event_id', $this->event->id)
            ->where('user_id', auth()->id())
            ->whereDate('available_at', $this->selectedDate)
            ->update([
                'location' => $this->selectedAtMyPlace ? 'my-place' : null,
            ]);

        $this->event->load(['users:id,name,email', 'availabilities.user:id,name,email']);
        $this->dispatch('event-availability-updated');
    }

    public function removeAvailability(): void
    {
        if ($this->selectedDate === null || auth()->user() === null) {
            return;
        }

        EventAvailability::query()
            ->where('event_id', $this->event->id)
            ->where('user_id', auth()->id())
            ->whereDate('available_at', $this->selectedDate)
            ->delete();

        $this->selectedAtMyPlace = false;

        $this->event->load(['users:id,name,email', 'availabilities.user:id,name,email']);
        $this->dispatch('event-availability-updated');
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

        return $this->event->availabilities
            ->where('user_id', auth()->id())
            ->contains(fn ($availability) => $availability->available_at->toDateString() === $this->selectedDate);
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
                return $availability->available_at->toDateString() === $this->selectedDate
                    && filled($availability->location)
                    && $availability->user !== null;
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

<div x-data="{ syncing: false, activeDate: null, syncStartedAt: 0 }"
    x-on:calendar-day-selected.window="activeDate = $event.detail.date; syncing = true; syncStartedAt = Date.now(); $wire.setSelectedDay($event.detail.date, $event.detail.label)"
    x-on:calendar-day-synced.window="if ($event.detail.date === activeDate) { const remaining = Math.max(0, 300 - (Date.now() - syncStartedAt)); setTimeout(() => { syncing = false }, remaining) }">
    <flux:modal name="calendar-day-modal" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg" x-text="$store.calendarDay.day.label"></flux:heading>
                <flux:text class="mt-1" x-text="$store.calendarDay.day.date"></flux:text>
            </div>

            <div x-show="syncing" x-cloak class="space-y-3">
                <flux:skeleton class="h-4 w-2/3" />
                <flux:skeleton class="h-4 w-full" />
                <flux:skeleton class="h-10 w-full" />
                <flux:skeleton class="h-9 w-full" />
            </div>

            <div x-show="!syncing" x-cloak class="space-y-4">
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

                @if ($this->selectedDayHasMyAvailability)
                    <flux:switch class="w-full cursor-pointer" label="{{ __('Mark as at my place') }}"
                        wire:model="selectedAtMyPlace" wire:change="saveLocation"></flux:switch>

                <div class="mt-2 space-y-1 text-sm">
                    @forelse ($this->selectedDayLocations as $locationEntry)
                        <div class="text-zinc-700 dark:text-zinc-200">
                            <span class="font-medium">{{ $locationEntry['name'] }}:</span>
                            <span>{{ $locationEntry['location'] }}</span>
                        </div>
                    @empty
                        <flux:text class="text-zinc-700 dark:text-zinc-200">
                            {{ __('No locations provided for this day.') }}
                        </flux:text>
                    @endforelse
                </div>
                @endif

                @if ($this->selectedDayHasMyAvailability)
                    <div class="flex flex-wrap gap-2">
                        <flux:button type="button" variant="danger" wire:click="removeAvailability" :loading="false"
                            icon="trash" size="sm" class="w-full" :label="__('Remove availability')">
                            {{ __('Remove Availability') }}
                        </flux:button>
                    </div>
                @else
                    <flux:field>
                        <flux:label>{{ __('Time') }}</flux:label>
                        <flux:select size="sm" wire:model="selectedTime">
                            @foreach ($this->timeOptions as $timeOption)
                                <option value="{{ $timeOption }}">
                                    {{ $timeOption === 'all-day' ? __('All day') : $timeOption }}
                                </option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:button type="button" variant="primary" wire:click="addAvailability" :loading="false"
                        icon="plus" size="sm" class="w-full" :label="__('Add availability')">
                        {{ __('Add Availability') }}
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
