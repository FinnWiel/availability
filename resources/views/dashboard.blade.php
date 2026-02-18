<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl"
                style="border-color: var(--color-accent); border-width: 2px; background-color: color-mix(in srgb, var(--color-accent) 10%, transparent);">
                {{-- <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" /> --}}

                <div class="relative z-10 flex h-full flex-col justify-between p-4 ">
                    <div class="flex flex-col gap-2">
                        <flux:text size="md" class="uppercase text-neutral-500 dark:text-neutral-400">
                            {{ __('Next available date') }}
                        </flux:text>
                        <div>
                            @if ($nextAvailableDateTime)
                                <p class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                    {{ $nextAvailableDateTime->timezone(config('app.timezone'))->format('D, M j Y') }}
                                </p>
                                <p class="text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $nextAvailableDateTime->timezone(config('app.timezone'))->format('g:i A') }}
                                </p>
                            @else
                                <p class="text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ __('No upcoming availability yet.') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="flex w-full justify-end">
                        @if ($nextAvailableDateTime)
                            @if ($nextAvailabilityLocation === 'my-place')
                                <flux:tooltip position="bottom" content="{{ __('At your place') }}">
                                    <span class="text-primary"
                                        data-test="dashboard-next-availability-host-pin">
                                        <flux:icon.map-pin class="size-6" />
                                    </span>
                                </flux:tooltip>
                            @else
                                <flux:tooltip position="bottom" content="{{ __('No host location set yet') }}">
                                    <span class="text-primary"
                                        data-test="dashboard-next-availability-host-pin-off">
                                        <flux:icon.map-pin-off class="size-6" />
                                    </span>
                                </flux:tooltip>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern
                    class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern
                    class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>
</x-layouts::app>
