<x-layouts::app :title="__('Events')">
    <div class="space-y-3" x-data="{
        createColor: '{{ old('color', '#2563EB') }}',
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
        <div>
            <flux:heading size="xl">{{ __('Events') }}</flux:heading>
            <flux:subheading>
                {{ __('Create events and assign each user to one or more events.') }}
            </flux:subheading>
        </div>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('status') }}
            </flux:callout>
        @endif

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                {{ $errors->first() }}
            </flux:callout>
        @endif

        <flux:navbar class="mb-5 border-b border-zinc-200 dark:border-zinc-700">
            <flux:navbar.item :href="route('admin.settings.users')" wire:navigate>
                {{ __('Users') }}
            </flux:navbar.item>

            <flux:navbar.item :href="route('admin.settings.events')" :current="true" wire:navigate>
                {{ __('Events') }}
            </flux:navbar.item>
        </flux:navbar>

        <flux:card>
            <form method="POST" action="{{ route('admin.events.store') }}"
                class="flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf

                <flux:input name="name" :label="__('Event title')" :value="old('name')" class="sm:max-w-sm"
                    required />

                <div class="grid gap-1">
                    <flux:label>{{ __('Color') }}</flux:label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="color" x-model="createColor"
                            class="h-10 w-12 rounded-md border border-zinc-300 bg-white p-1 dark:border-zinc-600 dark:bg-zinc-900"
                            value="{{ old('color', '#2563EB') }}">
                        <flux:text
                            class="rounded-md border border-zinc-200 px-2 py-1 text-xs uppercase tracking-wide dark:border-zinc-700"
                            x-text="createColor"></flux:text>
                    </div>
                </div>

                <flux:button type="submit" variant="primary">
                    {{ __('Create Event') }}
                </flux:button>
            </form>
        </flux:card>

        @if ($events->isEmpty())
            <flux:callout icon="information-circle">
                {{ __('Create your first event before assigning users.') }}
            </flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Assigned Users') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($events as $event)
                        <flux:table.row :key="$event->id">
                            <flux:table.cell variant="strong">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block size-3 rounded-full border border-zinc-300 dark:border-zinc-600"
                                        x-bind:style="'background-color: ' + {{ \Illuminate\Support\Js::from($event->color) }}"></span>
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
                                                'updateUrl' => route('admin.events.update', $event),
                                                'deleteUrl' => route('admin.events.destroy', $event),
                                                'deleteMessage' => __(
                                                    'This will permanently delete :name and remove all user assignments from it. This action cannot be undone.',
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
                                                'updateUrl' => route('admin.events.update', $event),
                                                'deleteUrl' => route('admin.events.destroy', $event),
                                                'deleteMessage' => __(
                                                    'This will permanently delete :name and remove all user assignments from it. This action cannot be undone.',
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

        <x-modals.edit-event-modal :users="$users" />

        <x-modals.confirm-delete-modal name="delete-event-modal" :title="__('Delete Event')" x-action="selectedEvent.deleteUrl"
            x-message="selectedEvent.deleteMessage" />
    </div>
</x-layouts::app>
