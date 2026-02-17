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
                {{ __('Update the event title, event color, and which users are assigned to it.') }}
            </flux:text>
        </div>

        <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
            <flux:input name="name" :label="__('Event title')" x-model="{{ $xEvent }}.name" required />

            <div class="grid gap-1">
                <flux:label>{{ __('Color') }}</flux:label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="{{ $xEvent }}.color"
                        class="h-10 w-12 rounded-md border border-zinc-300 bg-white p-1 dark:border-zinc-600 dark:bg-zinc-900">

                    <flux:input name="color" x-model="{{ $xEvent }}.color"
                        x-on:blur="{{ $xEvent }}.color = {{ $xEvent }}.color.toUpperCase()" class="w-28"
                        placeholder="#2563EB" required />
                </div>
            </div>
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
