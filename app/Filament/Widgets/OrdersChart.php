<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\PerformanceCache;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class OrdersChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected ?string $heading = 'Orders by Status';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        return Cache::remember(PerformanceCache::ORDERS_CHART, now()->addMinute(), function (): array {
            $palette = [
                '#f59e0b', '#3b82f6', '#8b5cf6', '#16a34a', '#ef4444',
                '#06b6d4', '#f97316', '#ec4899', '#84cc16', '#6366f1',
            ];

            $counts = Order::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $labels = [];
            $data = [];
            $bgColors = [];
            $i = 0;

            foreach ($counts as $status => $count) {
                $labels[] = ucfirst(str_replace('_', ' ', $status));
                $data[] = (int) $count;
                $bgColors[] = $palette[$i % count($palette)];
                $i++;
            }

            if (empty($labels)) {
                $labels = ['No orders'];
                $data = [0];
                $bgColors = ['#d1d5db'];
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Orders',
                        'data' => $data,
                        'backgroundColor' => $bgColors,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
            'cutout' => '60%',
        ];
    }
}
