@props([
    'name' => 'edit-event-modal',
    'users',
    'xEvent' => 'selectedEvent',
])

<flux:modal :name="$name" class="md:w-[38rem]" :dismissible="false">
    <form method="POST" x-bind:action="{{ $xEvent }}.updateUrl" class="space-y-5">
        @csrf
        @method('PATCH')

        <div>
            <flux:heading size="lg">{{ __('Edit Event') }}</flux:heading>
            <flux:text class="mt-2">
                {{ __('Update the event title and which users are assigned to it.') }}
            </flux:text>
        </div>

        <input type="hidden" name="color" x-model="{{ $xEvent }}.color" />

        <div class="grid gap-4 md:grid-cols-1 md:items-end">
            <flux:input name="name" :label="__('Event title')" x-model="{{ $xEvent }}.name" required />
        </div>

        <div class="space-y-2">
            <flux:select name="users[]" :label="__('Assigned users')"
                :description="__('Search and select one or more users for this event.')" variant="listbox" multiple
                searchable clearable selected-suffix="users" search:placeholder="{{ __('Search users...') }}"
                :placeholder="__('Choose users')" x-model="{{ $xEvent }}.userIds">
                @foreach ($users as $user)
                    <flux:select.option value="{{ $user->id }}">
                        {{ $user->name }} - {{ $user->email }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400"
                x-text="{{ $xEvent }}.userIds.length + ' selected'"></flux:text>
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
        </div>
    </form>
</flux:modal>
