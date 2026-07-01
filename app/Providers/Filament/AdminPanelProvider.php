<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\OrderStatusChart;
use App\Filament\Widgets\RecentOrdersChart;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TopProductsChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('KM Decor')
            ->colors(function () {
                try {
                    $settings = app(\App\Settings\GeneralSettings::class);

                    return [
                        'primary' => Color::hex($settings->color_primary),
                        'danger' => Color::hex($settings->color_danger),
                        'success' => Color::hex($settings->color_success),
                        'warning' => Color::hex($settings->color_warning),
                        'info' => Color::hex($settings->color_info),
                    ];
                } catch (\Exception) {
                    return [
                        'primary' => Color::hex('#061b73'),
                        'danger' => Color::hex('#ed1c24'),
                        'success' => Color::hex('#16a34a'),
                        'warning' => Color::hex('#c9a84c'),
                        'info' => Color::hex('#2563eb'),
                    ];
                }
            })
            ->navigationGroups([
                'Catalog',
                'Content',
                'Sales',
                'Users & Access',
            ])
            ->plugin(FilamentSpatieRolesPermissionsPlugin::make())
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StatsOverview::class,
                RecentOrdersChart::class,
                OrderStatusChart::class,
                TopProductsChart::class,
                LowStockProducts::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
