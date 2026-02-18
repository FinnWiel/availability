<?php

use App\Models\Event;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component
{
    public Collection $events;

    public Collection $users;

    public int $createdEventsCount;

    public ?int $maxEvents;

    public bool $canCreateEvent;

    public string $newEventName = '';

    public function mount(): void
    {
        $this->refreshPageData();
    }

    public function createEvent(): void
    {
        if (! $this->canCreateEvent) {
            Flux::toast(text: __('You have reached the event creation limit.'), variant: 'warning');

            return;
        }

        $this->resetErrorBag();
        $this->newEventName = '';

        $this->dispatch('open-create-event-modal');
    }

    public function storeEvent(): void
    {
        abort_if(auth()->user()?->cannot('create', Event::class), 403);

        $validated = $this->validate([
            'newEventName' => ['required', 'string', 'max:255', 'unique:events,name'],
        ], [
            'newEventName.required' => 'Please enter an event title.',
            'newEventName.unique' => 'An event with this name already exists.',
        ]);

        $event = Event::query()->create([
            'name' => $validated['newEventName'],
            'color' => '#2563EB',
            'created_by' => auth()->id(),
        ]);

        $event->users()->sync([auth()->id()]);

        $this->refreshPageData();
        $this->dispatch('close-create-event-modal');

        Flux::toast(text: __('Event created successfully.'), variant: 'success');
    }

    private function refreshPageData(): void
    {
        $currentUser = auth()->user();

        $eventsQuery = Event::query()
            ->with(['users:id,name,email'])
            ->withCount('users')
            ->orderBy('name');

        if (! $currentUser?->hasRole('admin')) {
            $eventsQuery->where('created_by', $currentUser?->id);
        }

        $this->events = $eventsQuery->get();
        $this->users = User::query()->orderBy('name')->get();
        $this->createdEventsCount = Event::query()
            ->where('created_by', auth()->id())
            ->count();
        $this->maxEvents = auth()->user()?->hasRole('admin') ? null : 2;
        $this->canCreateEvent = auth()->user()?->can('create', Event::class) ?? false;
    }
}; ?>

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
}" x-on:open-create-event-modal.window="$flux.modal('create-event-modal').show()"
    x-on:close-create-event-modal.window="$flux.modal('create-event-modal').close()">
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
                <flux:button icon="plus" variant="primary" wire:click="createEvent" label="{{ __('Create Event') }}" />
            @endcan
        </div>
    </div>

    @if ($this->events->isEmpty())
        <div x-data x-init="$flux.toast({ text: @js(__('No events yet. Create one to invite people and plan availability.')), variant: 'warning' })"></div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Invited Users') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->events as $event)
                    <flux:table.row :key="$event->id">
                        <flux:table.cell variant="strong">
                            <div class="flex items-center gap-2">
                                <span class="inline-block size-3 rounded-full border border-zinc-300 bg-accent dark:border-zinc-600"></span>
                                <span>{{ $event->name }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="lime">
                                {{ trans_choice('{1} :count user|[2,*] :count users', $event->users_count, ['count' => $event->users_count]) }}
                            </flux:badge>
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
    @endif

    <x-modals.edit-event-modal :users="$this->users" />

    <x-modals.confirm-delete-modal name="delete-event-modal" :title="__('Delete Event')" x-action="selectedEvent.deleteUrl"
        x-message="selectedEvent.deleteMessage" />

    <flux:modal name="create-event-modal" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Create Event') }}</flux:heading>
                <flux:text>{{ __('Set a name and color for your event.') }}</flux:text>
            </div>

            <flux:field>
                <flux:label>{{ __('Event title') }}</flux:label>
                <flux:input wire:model="newEventName" :placeholder="__('Team planning')" />
                <flux:error name="newEventName" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="button" variant="primary" wire:click="storeEvent">{{ __('Create Event') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
