<x-layouts::app :title="__('Users')">
    <div class="space-y-3" x-data="{
        selectedUser: {
            id: null,
            name: '',
            role: 'user',
            updateRoleUrl: '',
            deleteUrl: '',
            deleteMessage: '',
        },
    }">
        <div>
            <flux:heading size="xl">{{ __('Users') }}</flux:heading>
            <flux:subheading>
                {{ __('Only admins can access this page. Assign each account the admin or user role.') }}
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
            <flux:navbar.item :href="route('admin.settings.users')" :current="true" wire:navigate>
                {{ __('Users') }}
            </flux:navbar.item>

            <flux:navbar.item :href="route('admin.settings.events')" wire:navigate>
                {{ __('Events') }}
            </flux:navbar.item>
        </flux:navbar>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Current Role') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($user->hasRole('admin'))
                                <flux:badge color="sky">{{ __('Admin') }}</flux:badge>
                            @else
                                <flux:badge>{{ __('User') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:modal.trigger name="edit-user-role-modal">
                                    <flux:button type="button" variant="ghost" size="sm" icon="pencil-square"
                                        :label="__('Edit user')"
                                        x-on:click="selectedUser = {{ \Illuminate\Support\Js::from([
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'role' => $user->hasRole('admin') ? 'admin' : 'user',
                                            'updateRoleUrl' => route('admin.users.update-role', $user),
                                            'deleteUrl' => route('admin.users.destroy', $user),
                                            'deleteMessage' => __(
                                                'This will permanently delete :name and remove their event assignments. This action cannot be undone.',
                                                ['name' => $user->name],
                                            ),
                                        ]) }}" />
                                </flux:modal.trigger>

                                <flux:modal.trigger name="delete-user-modal">
                                    <flux:button type="button" color="red" size="sm" icon="trash"
                                        variant="primary" :label="__('Delete user')"
                                        x-on:click="selectedUser = {{ \Illuminate\Support\Js::from([
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'role' => $user->hasRole('admin') ? 'admin' : 'user',
                                            'updateRoleUrl' => route('admin.users.update-role', $user),
                                            'deleteUrl' => route('admin.users.destroy', $user),
                                            'deleteMessage' => __(
                                                'This will permanently delete :name and remove their event assignments. This action cannot be undone.',
                                                ['name' => $user->name],
                                            ),
                                        ]) }}" />
                                </flux:modal.trigger>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <x-modals.edit-user-role-modal />

        <x-modals.confirm-delete-modal name="delete-user-modal" :title="__('Delete User')" x-action="selectedUser.deleteUrl"
            x-message="selectedUser.deleteMessage" />
    </div>
</x-layouts::app>
