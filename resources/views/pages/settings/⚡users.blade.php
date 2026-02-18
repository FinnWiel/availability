<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function users(): Collection
    {
        return User::query()->with('roles')->orderBy('name')->get();
    }

    public function impersonateUser(int $userId)
    {
        $targetUser = User::query()->findOrFail($userId);

        $authorization = Gate::inspect('impersonate', $targetUser);

        if ($authorization->denied()) {
            Flux::toast(text: $authorization->message() ?: __('You are not allowed to impersonate this user.'), variant: 'danger');

            return null;
        }

        auth()->user()?->impersonate($targetUser);

        return redirect()->route('dashboard');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('User Settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Users')"
        :subheading="__('Only admins can access this page. Assign each account the admin or user role.')">
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
            @if (session('status'))
                <div x-data x-init="$flux.toast({ text: @js(session('status')), variant: 'success' })"></div>
            @endif

            @if ($errors->any())
                <div x-data x-init="$flux.toast({ text: @js($errors->first()), variant: 'danger' })"></div>
            @endif

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Email') }}</flux:table.column>
                    <flux:table.column>{{ __('Current Role') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->users as $user)
                        <flux:table.row :key="$user->id">
                            <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>
                                @foreach ($user->getRoleNames() as $role)
                                    <flux:text class="capitalize">{{ $role }}</flux:text>
                                    @if(!$loop->last)
                                        <span class="sr-only">,</span>
                                    @endif
                                @endforeach
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-2">
                                    @if (! auth()->user()?->is($user))
                                        <flux:button icon="hat-glasses" type="button" variant="primary"
                                            size="sm" :label="__('Impersonate user')"
                                            wire:click="impersonateUser({{ $user->id }})" />
                                    @endif

                                    <flux:modal.trigger name="edit-user-role-modal">
                                        <flux:button type="button" variant="ghost" size="sm" icon="pencil-square"
                                            :label="__('Edit user')"
                                            x-on:click="selectedUser = {{ \Illuminate\Support\Js::from([
                                                'id' => $user->id,
                                                'name' => $user->name,
                                                'role' => $user->hasRole('admin') ? 'admin' : 'user',
                                                'updateRoleUrl' => route('admin.users.update-role', $user),
                                                'deleteUrl' => route('admin.users.destroy', $user),
                                                'deleteMessage' => __('This will permanently delete :name and remove their event assignments. This action cannot be undone.', ['name' => $user->name]),
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
                                                'deleteMessage' => __('This will permanently delete :name and remove their event assignments. This action cannot be undone.', ['name' => $user->name]),
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
    </x-pages::settings.layout>
</section>
