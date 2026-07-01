<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;

class Dashboard extends BaseDashboard
{
    public function getWidgetsContentComponent(): Component
    {
        return Grid::make($this->getColumns())
            ->schema(function (): array {
                $components = [];

                foreach ($this->getWidgets() as $widgetKey => $widget) {
                    $widgetClass = $this->normalizeWidgetClass($widget);
                    $defaults = (new \ReflectionClass($widgetClass))->getDefaultProperties();

                    $livewire = Livewire::make(
                        $widgetClass,
                        fn (): array => [
                            ...$this->getWidgetData(),
                            ...($widget instanceof \Filament\Widgets\WidgetConfiguration
                                ? [...$widget->widget::getDefaultProperties(), ...$widget->getProperties()]
                                : $widgetClass::getDefaultProperties()),
                            ...(property_exists($this, 'filters') ? ['pageFilters' => $this->filters] : []),
                        ],
                    )->key("{$widgetClass}-{$widgetKey}")->liberatedFromContainerGrid();

                    $span = $defaults['columnSpan'] ?? 1;
                    $start = $defaults['columnStart'] ?? [];

                    if ($span !== 1 || filled($start)) {
                        $livewire->columnSpan($span);
                    }

                    if (filled($start)) {
                        $livewire->columnStart($start);
                    }

                    $components[] = $livewire;
                }

                return $components;
            });
    }
}
