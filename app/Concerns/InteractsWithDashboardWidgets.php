<?php

namespace App\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait InteractsWithDashboardWidgets
{
    /**
     * @return array<string, array{component: string, label: string, description: string, default_enabled: bool, default_col_span: int, default_row_span: int, min_col_span: int, max_col_span: int, min_row_span: int, max_row_span: int}>
     */
    protected function widgetDefinitions(): array
    {
        $widgetsPath = resource_path('views/components/widgets');

        if (! File::isDirectory($widgetsPath)) {
            return [];
        }

        $discoveredWidgets = collect(File::allFiles($widgetsPath))
            ->filter(fn ($file): bool => Str::endsWith($file->getFilename(), '.blade.php'))
            ->sortBy(fn ($file): string => $file->getRelativePathname())
            ->mapWithKeys(function ($file): array {
                $relativeName = Str::replaceLast('.blade.php', '', $file->getRelativePathname());
                $componentName = Str::of($relativeName)->replace('/', '.')->value();
                $widgetId = Str::of($relativeName)->replace('/', '-')->value();
                $label = Str::of($relativeName)
                    ->afterLast('/')
                    ->replace('-', ' ')
                    ->title()
                    ->value();

                return [
                    $widgetId => [
                        'component' => 'widgets.'.$componentName,
                        'label' => $label,
                        'description' => __('Manage visibility, order, and size for this widget.'),
                        'default_enabled' => true,
                        'default_col_span' => 2,
                        'default_row_span' => 1,
                        'min_col_span' => 1,
                        'max_col_span' => 4,
                        'min_row_span' => 1,
                        'max_row_span' => 1,
                    ],
                ];
            })
            ->all();

        return array_replace_recursive($discoveredWidgets, $this->widgetDefinitionOverrides());
    }

    /**
     * @param  array<int, mixed>  $widgets
     * @return array<int, array{id: string, enabled: bool, col_span: int, row_span: int}>
     */
    protected function normalizeWidgets(array $widgets): array
    {
        $definitions = $this->widgetDefinitions();
        $normalizedWidgets = [];
        $knownWidgetIds = [];

        foreach ($widgets as $widget) {
            if (! is_array($widget)) {
                continue;
            }

            $widgetId = (string) ($widget['id'] ?? '');

            if ($widgetId === '' || ! array_key_exists($widgetId, $definitions) || isset($knownWidgetIds[$widgetId])) {
                continue;
            }

            $definition = $definitions[$widgetId];

            $normalizedWidgets[] = [
                'id' => $widgetId,
                'enabled' => (bool) ($widget['enabled'] ?? $definition['default_enabled']),
                'col_span' => $this->normalizeSpan(
                    $widget['col_span'] ?? null,
                    $definition['min_col_span'],
                    $definition['max_col_span'],
                    $definition['default_col_span'],
                ),
                'row_span' => $this->normalizeSpan(
                    $widget['row_span'] ?? null,
                    $definition['min_row_span'],
                    $definition['max_row_span'],
                    $definition['default_row_span'],
                ),
            ];

            $knownWidgetIds[$widgetId] = true;
        }

        foreach ($definitions as $widgetId => $definition) {
            if (isset($knownWidgetIds[$widgetId])) {
                continue;
            }

            $normalizedWidgets[] = [
                'id' => $widgetId,
                'enabled' => $definition['default_enabled'],
                'col_span' => $definition['default_col_span'],
                'row_span' => $definition['default_row_span'],
            ];
        }

        return $normalizedWidgets;
    }

    protected function normalizeSpan(mixed $value, int $minimum, int $maximum, int $fallback): int
    {
        $normalizedValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($normalizedValue === false) {
            $normalizedValue = $fallback;
        }

        return max($minimum, min($maximum, (int) $normalizedValue));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function widgetDefinitionOverrides(): array
    {
        return [
            'next-available' => [
                'label' => __('Next available dates'),
                'description' => __('Shows your upcoming availability for each event.'),
                'default_col_span' => 4,
                'default_row_span' => 1,
                'min_row_span' => 1,
                'max_row_span' => 1,
            ],
        ];
    }
}
