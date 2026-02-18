<?php

use App\Concerns\InteractsWithDashboardWidgets;
use App\Models\EventAvailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    use InteractsWithDashboardWidgets;

    /**
     * @var array<int, array{id: string, enabled: bool, col_span: int, row_span: int}>
     */
    public array $widgets = [];

    /**
     * @var array<string, array{component: string, label: string, description: string, default_enabled: bool, default_col_span: int, default_row_span: int, min_col_span: int, max_col_span: int, min_row_span: int, max_row_span: int}>
     */
    public array $definitions = [];

    public Collection $nextAvailabilitiesByEvent;

    public function mount(): void
    {
        $this->definitions = $this->widgetDefinitions();
        $this->nextAvailabilitiesByEvent = $this->resolveNextAvailabilitiesByEvent();
        $this->widgets = $this->resolveWidgets();
    }

    #[On('dashboard-widgets-updated')]
    public function refreshWidgets(): void
    {
        $this->definitions = $this->widgetDefinitions();
        $this->widgets = $this->resolveWidgets();
    }

    /**
     * @return array<int, array{id: string, enabled: bool, col_span: int, row_span: int}>
     */
    private function resolveWidgets(): array
    {
        $storedWidgets = request()->user()?->dashboard_widgets;

        return $this->normalizeWidgets(is_array($storedWidgets) ? $storedWidgets : []);
    }

    private function resolveNextAvailabilitiesByEvent(): Collection
    {
        $userId = request()->user()?->id;

        if ($userId === null) {
            return collect();
        }

        return EventAvailability::query()
            ->with('event:id,name')
            ->where('available_at', '>=', now())
            ->whereHas('event.users', function (Builder $query) use ($userId): void {
                $query->where('users.id', $userId);
            })
            ->orderBy('available_at')
            ->get()
            ->unique('event_id')
            ->values();
    }
};
?>

<div class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:text>{{ __('Here you can find an overview of your upcoming events and availabilities.') }}</flux:text>
        </div>

        <flux:modal.trigger name="dashboard-widgets-modal">
            <flux:button type="button" icon="paintbrush" variant="ghost" />
        </flux:modal.trigger>
    </div>

    <livewire:dashboard.widgets-modal />

    @php
        $enabledWidgets = collect($this->widgets)
            ->filter(fn(array $widget): bool => $widget['enabled'])
            ->map(function (array $widget): ?array {
                $definition = $this->definitions[$widget['id']] ?? null;

                if ($definition === null) {
                    return null;
                }

                return [
                    'component' => $definition['component'],
                    'col_span' => $widget['col_span'],
                ];
            })
            ->filter();
    @endphp

    @if ($enabledWidgets->isEmpty())
        <flux:card>
            <flux:text>{{ __('No widgets enabled') }}</flux:text>
        </flux:card>
    @else
        <div class="grid w-full grid-cols-1 gap-4 auto-rows:minmax(250px,25%) md:grid-cols-4">
            @foreach ($enabledWidgets as $widget)
                    @php
                        $widgetClasses = ['md:col-span-' . max(1, min(4, (int) ($widget['col_span'] ?? 1)))];
                    @endphp

                    <div @class($widgetClasses)>
                        <x-dynamic-component :component="$widget['component']" :next-availabilities-by-event="$this->nextAvailabilitiesByEvent" />
                    </div>
            @endforeach
        </div>
    @endif
</div>
