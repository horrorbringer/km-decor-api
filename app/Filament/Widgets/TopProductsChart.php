<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;

class TopProductsChart extends ChartWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;
    protected int | string | array $columnStart = 2;
    protected ?string $maxHeight = '350px';
    protected ?string $heading = 'Top Selling Products';

    protected function getData(): array
    {
        $top = OrderItem::selectRaw('product_name, sum(quantity) as total_qty')
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Units Sold',
                    'data' => $top->pluck('total_qty')->toArray(),
                    'backgroundColor' => ['#061b73', '#2563eb', '#16a34a', '#f59e0b', '#8b5cf6'],
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $top->pluck('product_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['precision' => 0],
                ],
                'y' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }
}
