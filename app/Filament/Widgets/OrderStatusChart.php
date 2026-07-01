<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;
    protected int | string | array $columnStart = 2;
    protected ?string $maxHeight = '350px';
    protected ?string $heading = 'Order Status Distribution';

    protected function getData(): array
    {
        $colors = [
            'pending' => '#f59e0b',
            'confirmed' => '#3b82f6',
            'shipped' => '#8b5cf6',
            'completed' => '#16a34a',
            'cancelled' => '#ef4444',
        ];

        $counts = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $labels = [];
        $data = [];
        $bgColors = [];

        foreach (['pending', 'confirmed', 'shipped', 'completed', 'cancelled'] as $status) {
            $count = (int) ($counts[$status] ?? 0);
            if ($count > 0) {
                $labels[] = ucfirst($status);
                $data[] = $count;
                $bgColors[] = $colors[$status];
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $data,
                    'backgroundColor' => $bgColors,
                    'borderColor' => $bgColors,
                ],
            ],
            'labels' => $labels,
        ];
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
            'cutout' => '55%',
        ];
    }
}
