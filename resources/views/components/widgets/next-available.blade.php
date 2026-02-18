@props([
    'nextAvailabilitiesByEvent' => null,
])


<div class="w-full " x-data="{ activeSlide: 0, totalSlides: {{ $nextAvailabilitiesByEvent->count() }} }">
    @if ($nextAvailabilitiesByEvent->isEmpty())
        <flux:card>
            <div class="space-y-2 p-2 ">
                <flux:heading size="lg">{{ __('No upcoming availability yet') }}</flux:heading>
                <flux:text>{{ __('Add availability in one of your event calendars to see it here.') }}</flux:text>
            </div>
        </flux:card>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex transition-transform duration-300 ease-out w-"
                x-bind:style="`transform: translateX(-${activeSlide * 100}%);`">
                @foreach ($nextAvailabilitiesByEvent as $availability)
                    <div class="flex min-h-52 flex-col justify-between gap-4 w-full shrink-0 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <flux:heading size="xl">{{ $availability->event?->name }}
                                </flux:heading>
                            </div>

                            @if ($availability->location === 'my-place')
                                <flux:tooltip position="bottom" content="{{ __('At your place') }}">
                                    <span class="text-primary" data-test="dashboard-next-availability-host-pin">
                                        <flux:icon.map-pin class="size-6" />
                                    </span>
                                </flux:tooltip>
                            @else
                                <flux:tooltip position="bottom" content="{{ __('No host location set yet') }}">
                                    <span class="text-primary" data-test="dashboard-next-availability-host-pin-off">
                                        <flux:icon.map-pin-off class="size-6" />
                                    </span>
                                </flux:tooltip>
                            @endif
                        </div>

                        <div class="flex items-end justify-between">
                            <div>
                                <flux:text>
                                    {{ $availability->available_at->timezone(config('app.timezone'))->format('g:i A') }}
                                </flux:text>
                                <flux:heading size="xl">
                                    {{ $availability->available_at->timezone(config('app.timezone'))->format('D, M j') }}
                                </flux:heading>
                            </div>
                            <flux:button size="sm" variant="ghost" icon="corner-down-right"
                                :href="route('events.show', $availability->event)" wire:navigate />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>


        @if ($nextAvailabilitiesByEvent->count() > 1)
            <div class="flex items-center justify-center gap-4 p-2">
                <flux:button type="button" variant="ghost" size="xs" icon="chevron-left"
                    x-on:click="activeSlide = activeSlide === 0 ? totalSlides - 1 : activeSlide - 1" class="text-zinc-700 dark:text-white" />

                <div class="flex items-center justify-center gap-2">
                    @foreach ($nextAvailabilitiesByEvent as $availability)
                        <button type="button" class="h-2.5 rounded-full bg-zinc-300 transition-all dark:bg-white"
                            x-bind:class="activeSlide === {{ $loop->index }} ? 'w-7 bg-zinc-300 dark:bg-white' : 'w-2.5'"
                            x-on:click="activeSlide = {{ $loop->index }}"
                            aria-label="{{ __('Go to slide :number', ['number' => $loop->iteration]) }}"></button>
                    @endforeach
                </div>

                <flux:button type="button" variant="ghost" size="xs" icon="chevron-right"
                    x-on:click="activeSlide = activeSlide === totalSlides - 1 ? 0 : activeSlide + 1" class="text-zinc-700 dark:text-white" />
            </div>
        @endif
    @endif
</div>
