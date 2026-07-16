<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\PerformanceCache;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected ?string $heading = 'Sales';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        return Cache::remember(PerformanceCache::SALES_CHART, now()->addMinute(), function (): array {
            $results = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', Carbon::today()->subDays(6)->startOfDay())
                ->groupBy('date')
                ->pluck('count', 'date');

            $labels = collect();
            $data = collect();

            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $labels->push($date->format('D'));
                $data->push((int) ($results[$date->toDateString()] ?? 0));
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Orders',
                        'data' => $data->toArray(),
                        'backgroundColor' => '#2563eb',
                        'borderColor' => '#2563eb',
                        'tension' => 0.3,
                        'fill' => true,
                    ],
                ],
                'labels' => $labels->toArray(),
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }
}
