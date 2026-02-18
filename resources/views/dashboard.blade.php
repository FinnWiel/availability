<x-layouts::app :title="__('Dashboard')">
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <div class="mb-2">
                <flux:heading size="xl">Welcome, {{ auth()->user()->name }}</flux:heading>
                <flux:text>{{ __('Here you can find an overview of your upcoming events and availabilities.') }}</flux:text>
            </div>
            <flux:button icon="paintbrush" variant="ghost"></flux:button>
        </div>
        <div class="grid grid-cols-4 w-full gap-4">
            <x-widgets.next-available :next-availabilities-by-event="$nextAvailabilitiesByEvent ?? collect()" />
        </div>
    </div>

</x-layouts::app>
