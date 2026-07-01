<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Orders', Order::count())
                ->description('All time orders')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->chart($this->dailyCount(Order::class, 'created_at'))
                ->color('primary'),

            Stat::make('Active Products', Product::where('status', 'published')->count())
                ->description(Product::where('status', 'draft')->count() . ' drafts')
                ->descriptionIcon('heroicon-o-cube')
                ->chart($this->dailyCount(Product::class, 'created_at', [['status', 'published']]))
                ->color('success'),

            Stat::make('Active Services', Service::where('is_active', true)->count())
                ->description(Service::where('is_active', false)->count() . ' inactive')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->chart($this->dailyCount(Service::class, 'created_at', [['is_active', true]]))
                ->color('warning'),

            Stat::make('Registered Users', User::count())
                ->description(User::where('is_active', true)->count() . ' active')
                ->descriptionIcon('heroicon-o-users')
                ->chart($this->dailyCount(User::class, 'created_at'))
                ->color('info'),
        ];
    }

    private function dailyCount(string $model, string $dateColumn, array $conditions = []): array
    {
        $query = $model::selectRaw("DATE($dateColumn) as date, COUNT(*) as count")
            ->whereDate($dateColumn, '>=', Carbon::today()->subDays(6))
            ->groupBy('date');

        foreach ($conditions as $condition) {
            $query->where($condition[0], $condition[1]);
        }

        $results = $query->pluck('count', 'date');

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = (int) ($results[Carbon::today()->subDays($i)->toDateString()] ?? 0);
        }
        return $data;
    }
}
