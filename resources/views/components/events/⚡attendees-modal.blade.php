<?php

use App\Models\Event;
use Livewire\Component;

new class extends Component
{
    public Event $event;

    public function mount(Event $event): void
    {
        $this->event = $event->loadMissing('users:id,name,email');
    }
};
?>

<flux:modal name="attendees-modal" class="md:w-[30rem]">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">{{ __('Attendees') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Everyone assigned to this event.') }}</flux:text>
        </div>

        <div class="space-y-2">
            @foreach ($event->users as $user)
                @php
                    $avatarUrl = $user->profile_photo_url ?? ($user->avatar_url ?? null);
                @endphp

                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                    <flux:avatar size="sm" :name="$user->name" :src="$avatarUrl" />

                    <div>
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</div>
                        <div class="text-zinc-500 dark:text-zinc-400">{{ $user->email }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</flux:modal>
