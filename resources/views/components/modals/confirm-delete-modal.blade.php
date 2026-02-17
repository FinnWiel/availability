@props([
    'name',
    'action' => null,
    'title' => null,
    'message' => null,
    'xAction' => null,
    'xTitle' => null,
    'xMessage' => null,
    'confirmLabel' => __('Yes, delete'),
    'cancelLabel' => __('No'),
])

<flux:modal :name="$name" class="md:w-96" :dismissible="false">
    <div class="space-y-4">
        <div>
            @if ($xTitle)
                <flux:heading size="lg" x-text="{{ $xTitle }}"></flux:heading>
            @else
                <flux:heading size="lg">{{ $title }}</flux:heading>
            @endif

            @if ($xMessage)
                <flux:text class="mt-2" x-text="{{ $xMessage }}"></flux:text>
            @else
                <flux:text class="mt-2">{{ $message }}</flux:text>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost">
                    {{ $cancelLabel }}
                </flux:button>
            </flux:modal.close>

            @if ($xAction)
                <form method="POST" x-bind:action="{{ $xAction }}">
                    @csrf
                    @method('DELETE')

                    <flux:button type="submit" variant="danger">
                        {{ $confirmLabel }}
                    </flux:button>
                </form>
            @else
                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method('DELETE')

                    <flux:button type="submit" variant="danger">
                        {{ $confirmLabel }}
                    </flux:button>
                </form>
            @endif
        </div>
    </div>
</flux:modal>
