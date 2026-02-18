@props([
    'day',
    'availableUsers',
    'hostNames',
])

<button type="button"
    @if ($day['isCommonDay']) style="border-color: var(--color-accent); border-width: 2px; background-color: color-mix(in srgb, var(--color-accent) 10%, transparent);" @endif
    {{ $attributes->merge(['class' => 'h-full aspect-square rounded-lg border p-2 text-left transition hover:border-zinc-400 hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/60 '.($day['inCurrentMonth'] ? 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/50' : 'border-zinc-100 bg-zinc-50/70 text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900/20 dark:text-zinc-500').' '.($day['isCommonDay'] ? 'border-2 border-accent bg-accent/10 dark:border-accent dark:bg-accent/10' : '').' '.($day['isToday'] ? 'ring-2 ring-white' : '')]) }}>
    <div class="flex h-full flex-col justify-between">
        <div class="flex items-start justify-between">
            <div class="flex flex-col items-start justify-between">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ trans_choice('{1} :count attendee|[2,*] :count attendees', $day['attendeeCount'], ['count' => $day['attendeeCount']]) }}
                </div>

                @if ($day['hasMyAvailability'])
                    <div class="text-xs font-medium text-accent">
                        {{ __('You are available') }}
                    </div>
                @endif
            </div>

            @if ($hostNames->isNotEmpty())
                <flux:tooltip position="bottom" content="{{ $hostNames->implode(', ') }} {{ __('can host at their place') }}">
                    <span class="text-zinc-500 dark:text-zinc-400" data-test="availability-host-pin-{{ $day['date'] }}">
                        <flux:icon.map-pin class="size-5" />
                    </span>
                </flux:tooltip>
            @elseif ($day['isCommonDay'])
                <flux:tooltip position="bottom" content="{{ __('No host location set yet') }}">
                    <span class="text-zinc-400 dark:text-zinc-500" data-test="availability-host-pin-off-{{ $day['date'] }}">
                        <flux:icon.map-pin-off class="size-5" />
                    </span>
                </flux:tooltip>
            @endif
        </div>

        <div class="flex items-end justify-between gap-2">
            @if ($availableUsers->isNotEmpty())
                <div class="flex -space-x-1" data-test="availability-avatars-{{ $day['date'] }}">
                    @foreach ($availableUsers->take(3) as $availableUser)
                        @php
                            $availableUserAvatar = $availableUser->profile_photo_url ?? ($availableUser->avatar_url ?? null);
                        @endphp

                        <div data-test="availability-avatar-{{ $day['date'] }}-user-{{ $availableUser->id }}">
                            <flux:avatar circle size="sm" :name="$availableUser->name" :src="$availableUserAvatar" />
                        </div>
                    @endforeach

                    @if ($availableUsers->count() > 3)
                        <div
                            class="flex size-6 items-center justify-center rounded-full bg-zinc-200 text-[10px] font-semibold text-zinc-700 ring-2 ring-white dark:bg-zinc-700 dark:text-zinc-200 dark:ring-zinc-900">
                            +{{ $availableUsers->count() - 3 }}
                        </div>
                    @endif
                </div>
            @else
                <div></div>
            @endif

            <div
                class="text-right text-lg font-semibold leading-none {{ $day['isCommonDay'] ? 'text-accent' : ($day['inCurrentMonth'] ? 'text-zinc-900 dark:text-zinc-100' : '') }}">
                {{ $day['day'] }}
            </div>
        </div>
    </div>
</button>
