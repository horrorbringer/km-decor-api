<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RecentOrdersChart extends ChartWidget
{
    protected ?string $heading = 'Orders (Last 30 Days)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;
    protected ?string $maxHeight = '500px';

    protected function getData(): array
    {
        $results = Order::selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->whereDate('created_at', '>=', Carbon::today()->subDays(29))
            ->groupBy('date')
            ->pluck('count', 'date');

        $labels = collect();
        $data = collect();

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels->push($date->format('M d'));
            $data->push((int) ($results[$date->toDateString()] ?? 0));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $data->toArray(),
                    'backgroundColor' => '#061b73',
                    'borderColor' => '#061b73',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
