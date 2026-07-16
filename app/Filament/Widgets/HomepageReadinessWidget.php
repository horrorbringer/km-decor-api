<?php

namespace App\Filament\Widgets;

use App\Support\HomepageContentReadiness;
use Filament\Widgets\Widget;

class HomepageReadinessWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.homepage-readiness-widget';

    protected function getViewData(): array
    {
        return app(HomepageContentReadiness::class)->summary();
    }
}
