<?php

use App\Models\Event;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Collection $events;

    public function mount(): void
    {
        $this->refreshEvents();
    }

    #[On('event-created')]
    public function refreshEvents(): void
    {
        $currentUser = request()->user();

        if ($currentUser === null) {
            $this->events = collect();

            return;
        }

        $this->events = Event::query()
            ->whereHas('users', fn ($query) => $query->where('users.id', $currentUser->id))
            ->orderBy('name')
            ->get();
    }
};
?>

<div class="contents">
    @forelse ($this->events as $event)
        <flux:sidebar.item icon="calendar" :href="route('events.show', $event)"
            :current="request()->routeIs('events.show') && request()->route('event')?->is($event)" wire:navigate>
            {{ $event->name }}
        </flux:sidebar.item>
    @empty
        <flux:sidebar.item icon="calendar" disabled>
            {{ __('No events yet') }}
        </flux:sidebar.item>
    @endforelse
</div>
