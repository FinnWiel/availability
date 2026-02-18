<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Collection $events;

    public Collection $users;

    public function mount(): void
    {
        $this->refreshPageData();
    }

    #[On('event-created')]
    public function refreshEventList(): void
    {
        $this->refreshPageData();
    }

    private function refreshPageData(): void
    {
        $currentUser = request()->user();

        $eventsQuery = Event::query()
            ->with(['users:id,name,email'])
            ->withCount('users')
            ->orderBy('name');

        if (! $currentUser?->hasRole('admin')) {
            $eventsQuery->where('created_by', $currentUser?->id);
        }

        $this->events = $eventsQuery->get();
        $this->users = User::query()->orderBy('name')->get();
    }
};
?>

<div class="space-y-4" x-data="{
    selectedEvent: {
        id: null,
        name: '',
        color: '#2563EB',
        userIds: [],
        updateUrl: '',
        deleteUrl: '',
        deleteMessage: '',
    },
}">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">{{ __('Events') }}</flux:heading>
            <flux:subheading>
                @if (auth()->user()->hasRole('admin'))
                    {{ __('Create events and invite users. Admin accounts can create unlimited events.') }}
                @else
                    {{ __('Create your events and invite users. You can create up to 2 events.') }}
                @endif
            </flux:subheading>
        </div>
        <div>
            @can('create', Event::class)
                <flux:modal.trigger name="create-event-modal">
                    <flux:button icon="plus" variant="primary" :label="__('Create Event')" />
                </flux:modal.trigger>
            @endcan
        </div>
    </div>

    @if (! $this->events->isEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->events as $event)
                    <flux:table.row :key="$event->id">
                        <flux:table.cell variant="strong">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-block size-3 rounded-full border border-zinc-300 bg-accent dark:border-zinc-600"></span>
                                <span>{{ $event->name }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:modal.trigger name="edit-event-modal">
                                    <flux:button type="button" variant="ghost" size="sm" icon="pencil-square"
                                        :label="__('Edit event')"
                                        x-on:click="selectedEvent = {{ \Illuminate\Support\Js::from([
                                            'id' => $event->id,
                                            'name' => $event->name,
                                            'color' => $event->color,
                                            'userIds' => $event->users->pluck('id')->values()->all(),
                                            'updateUrl' => route('events.update', $event),
                                            'deleteUrl' => route('events.destroy', $event),
                                            'deleteMessage' => __(
                                                'This will permanently delete :name and remove all invitees from it. This action cannot be undone.',
                                                ['name' => $event->name],
                                            ),
                                        ]) }}" />
                                </flux:modal.trigger>

                                <flux:modal.trigger name="delete-event-modal">
                                    <flux:button type="button" color="red" size="sm" icon="trash"
                                        variant="primary" :label="__('Delete event')"
                                        x-on:click="selectedEvent = {{ \Illuminate\Support\Js::from([
                                            'id' => $event->id,
                                            'name' => $event->name,
                                            'color' => $event->color,
                                            'userIds' => $event->users->pluck('id')->values()->all(),
                                            'updateUrl' => route('events.update', $event),
                                            'deleteUrl' => route('events.destroy', $event),
                                            'deleteMessage' => __(
                                                'This will permanently delete :name and remove all invitees from it. This action cannot be undone.',
                                                ['name' => $event->name],
                                            ),
                                        ]) }}" />
                                </flux:modal.trigger>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <flux:text>{{ __('No events yet. Create one to invite people and plan availability.') }}</flux:text>
    @endif

    <x-modals.edit-event-modal :users="$this->users" />

    <x-modals.confirm-delete-modal name="delete-event-modal" :title="__('Delete Event')" x-action="selectedEvent.deleteUrl"
        x-message="selectedEvent.deleteMessage" />

    <livewire:events.create-event-modal />
</div>
