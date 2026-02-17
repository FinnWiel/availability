@props([
    'name' => 'edit-user-role-modal',
    'xUser' => 'selectedUser',
])

<flux:modal :name="$name" class="md:w-96">
    <form method="POST" x-bind:action="{{ $xUser }}.updateRoleUrl" class="space-y-4">
        @csrf
        @method('PATCH')

        <div>
            <flux:heading size="lg">{{ __('Edit User') }}</flux:heading>
            <flux:text class="mt-2" x-text="'Change the role for ' + {{ $xUser }}.name + '.'"></flux:text>
        </div>

        <flux:select name="role" :label="__('Role')" x-model="{{ $xUser }}.role">
            <option value="admin">{{ __('Admin') }}</option>
            <option value="user">{{ __('User') }}</option>
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
</flux:modal>
