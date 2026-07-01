<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $user, string $token): string {
            $query = http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);

            return rtrim(config('app.frontend_url'), '/').'/reset-password?'.$query;
        });

        Category::observe(CategoryObserver::class);
        Brand::observe(BrandObserver::class);
    }
}
