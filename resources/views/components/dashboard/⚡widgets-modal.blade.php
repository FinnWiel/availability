<?php

use App\Concerns\InteractsWithDashboardWidgets;
use Livewire\Component;

new class extends Component
{
    use InteractsWithDashboardWidgets;

    /**
     * @var array<int, array{id: string, enabled: bool, col_span: int, row_span: int}>
     */
    public array $widgets = [];

    /**
     * @var array<string, array{component: string, label: string, description: string, default_enabled: bool, default_col_span: int, default_row_span: int, min_col_span: int, max_col_span: int, min_row_span: int, max_row_span: int}>
     */
    public array $definitions = [];

    public ?string $expandedWidgetId = null;

    public function mount(): void
    {
        $this->definitions = $this->widgetDefinitions();

        $storedWidgets = request()->user()?->dashboard_widgets;
        $this->widgets = $this->normalizeWidgets(is_array($storedWidgets) ? $storedWidgets : []);

        $this->persistWidgets();
    }

    public function updatedWidgets(): void
    {
        $this->persistWidgets();
    }

    public function sortWidget(?string $widgetId, int $position): void
    {
        if ($widgetId === null || $widgetId === '') {
            return;
        }

        $currentIndex = collect($this->widgets)->search(fn (array $widget): bool => $widget['id'] === $widgetId);

        if ($currentIndex === false) {
            return;
        }

        $widgets = collect($this->widgets);
        $widget = $widgets->pull($currentIndex);

        if (! is_array($widget)) {
            return;
        }

        $widgets = $widgets->values();

        $targetPosition = max(0, min($widgets->count(), $position));

        $widgets->splice($targetPosition, 0, [$widget]);

        $this->widgets = $widgets->values()->all();

        $this->persistWidgets();
    }

    public function decrementColSpan(int $index): void
    {
        if (! isset($this->widgets[$index])) {
            return;
        }

        $widgetId = (string) ($this->widgets[$index]['id'] ?? '');
        $definition = $this->definitions[$widgetId] ?? null;

        if ($definition === null) {
            return;
        }

        $minimum = (int) $definition['min_col_span'];
        $current = (int) ($this->widgets[$index]['col_span'] ?? $definition['default_col_span']);

        if ($current <= $minimum) {
            return;
        }

        $this->widgets[$index]['col_span'] = max($minimum, $current - 1);

        $this->persistWidgets();
    }

    public function incrementColSpan(int $index): void
    {
        if (! isset($this->widgets[$index])) {
            return;
        }

        $widgetId = (string) ($this->widgets[$index]['id'] ?? '');
        $definition = $this->definitions[$widgetId] ?? null;

        if ($definition === null) {
            return;
        }

        $maximum = (int) $definition['max_col_span'];
        $current = (int) ($this->widgets[$index]['col_span'] ?? $definition['default_col_span']);

        if ($current >= $maximum) {
            return;
        }

        $this->widgets[$index]['col_span'] = min($maximum, $current + 1);

        $this->persistWidgets();
    }

    private function persistWidgets(): void
    {
        $this->widgets = $this->normalizeWidgets($this->widgets);

        request()
            ->user()
            ?->forceFill([
                'dashboard_widgets' => $this->widgets,
            ])
            ->save();

        $this->dispatch('dashboard-widgets-updated');
    }

    public function expandWidget(?string $widgetId): void
    {
        if ($widgetId === null || $widgetId === '') {
            return;
        }

        $definition = $this->definitions[$widgetId] ?? null;

        if ($definition === null) {
            return;
        }

        if ($this->expandedWidgetId === $widgetId) {
            $this->expandedWidgetId = null;

            return;
        }

        $this->expandedWidgetId = $widgetId;
    }
};
?>

<div>
    <flux:modal name="dashboard-widgets-modal" class="md:w-[36rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Customize widgets') }}</flux:heading>
                <flux:text>{{ __('Control which dashboard widgets are visible for your account.') }}</flux:text>
            </div>

            <div class="space-y-3" wire:sort="sortWidget">
                @foreach ($this->widgets as $index => $widget)
                    @php
                        $definition = $this->definitions[$widget['id']] ?? null;
                        $isExpanded = $this->expandedWidgetId === $widget['id'];
                    @endphp

                    @continue($definition === null)

                    <div wire:key="widget-{{ $widget['id'] }}" wire:sort:item="{{ $widget['id'] }}"
                        class="overflow-hidden rounded-xl border border-zinc-300 dark:border-zinc-700">
                        <div wire:click="expandWidget('{{ $widget['id'] }}')"
                            class="flex cursor-pointer items-center justify-between gap-4 p-2 px-4">
                            <div class="flex items-center gap-4">
                                <button type="button" wire:sort:handle wire:click.stop
                                    class="cursor-grab text-zinc-500 transition hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                                    aria-label="{{ __('Drag to reorder :widget', ['widget' => $definition['label']]) }}">
                                    <flux:icon.arrow-down-up class="size-5" />
                                </button>

                                <div class="space-y-1">
                                    <flux:heading size="sm">{{ $definition['label'] }}</flux:heading>
                                    <flux:text size="sm">{{ $definition['description'] }}</flux:text>
                                </div>
                            </div>

                            <button type="button" class="transition">
                                <flux:icon.chevron-down @class(['size-4 transition-transform', 'rotate-180' => $isExpanded]) />
                            </button>
                        </div>

                        @if ($isExpanded)
                            <div class=" p-2">
                                @php
                                    $currentColSpan = (int) ($widget['col_span'] ?? $definition['default_col_span']);
                                @endphp

                                <div class="px-4 py-3 dark:border-zinc-700">
                                    <flux:switch wire:model.live="widgets.{{ $index }}.enabled"
                                        :label="__('Show widget on dashboard')" />
                                </div>
                                <div class="px-4 pb-3">
                                    <flux:field>
                                        <flux:label>{{ __('Column span') }}</flux:label>
                                        <div class="flex items-center gap-2">
                                            <button type="button" wire:click="decrementColSpan({{ $index }})"
                                                @disabled($currentColSpan <= $definition['min_col_span'])
                                                class="inline-flex size-9 items-center justify-center rounded-lg border border-zinc-300 text-zinc-700 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                                <span aria-hidden="true">-</span>
                                                <span class="sr-only">{{ __('Decrease column span') }}</span>
                                            </button>

                                            <flux:input wire:model.live="widgets.{{ $index }}.col_span" type="number"
                                                min="{{ $definition['min_col_span'] }}" max="{{ $definition['max_col_span'] }}"
                                                class="text-center" />

                                            <button type="button" wire:click="incrementColSpan({{ $index }})"
                                                @disabled($currentColSpan >= $definition['max_col_span'])
                                                class="inline-flex size-9 items-center justify-center rounded-lg border border-zinc-300 text-zinc-700 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                                <span aria-hidden="true">+</span>
                                                <span class="sr-only">{{ __('Increase column span') }}</span>
                                            </button>
                                        </div>
                                    </flux:field>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </flux:modal>
</div>
