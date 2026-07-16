<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class PerformanceCache
{
    public const HOMEPAGE = 'api.homepage.v1';

    public const HOMEPAGE_READINESS = 'admin.homepage-readiness.v1';

    public const SALES_CHART = 'admin.dashboard.sales-chart.v1';

    public const ORDERS_CHART = 'admin.dashboard.orders-chart.v1';

    public const TOP_PRODUCTS_CHART = 'admin.dashboard.top-products-chart.v1';

    public static function forgetContent(): void
    {
        Cache::forget(self::HOMEPAGE);
        Cache::forget(self::HOMEPAGE_READINESS);
    }

    public static function forgetOrders(): void
    {
        Cache::forget(self::SALES_CHART);
        Cache::forget(self::ORDERS_CHART);
        Cache::forget(self::TOP_PRODUCTS_CHART);
    }
}
