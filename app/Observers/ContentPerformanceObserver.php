<?php

namespace App\Observers;

use App\Support\PerformanceCache;

class ContentPerformanceObserver
{
    public function saved(): void
    {
        PerformanceCache::forgetContent();
    }

    public function deleted(): void
    {
        PerformanceCache::forgetContent();
    }

    public function restored(): void
    {
        PerformanceCache::forgetContent();
    }
}
