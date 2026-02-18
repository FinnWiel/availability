@php
    $managedEvents = auth()->user()?->createdEvents()->select('id', 'name')->orderBy('name')->get() ?? collect();
@endphp

<div class="space-y-4 w-full h-full rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 overflow-auto">
    <div class="space-y-1">
        <flux:text size="sm" class="uppercase tracking-widest text-zinc-500 dark:text-zinc-400">
            {{ __('Managed Events') }}
        </flux:text>
        <flux:heading size="lg">{{ __('Your events') }}</flux:heading>
    </div>

    @if ($managedEvents->isEmpty())
        <flux:text>{{ __('You are not managing any events yet.') }}</flux:text>
    @else
        <div class="space-y-2">
            @foreach ($managedEvents as $event)
                <div
                    class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 px-3 py-2">
                    <flux:text class="truncate">{{ $event->name }}</flux:text>
                    <flux:button size="xs" variant="ghost" icon="corner-down-right"
                        :href="route('events.show', $event)" wire:navigate />
                </div>
            @endforeach
        </div>
    @endif
</div>
