<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Project;
use App\Models\Service;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\ContentPerformanceObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
        Product::observe(ContentPerformanceObserver::class);
        ProductImage::observe(ContentPerformanceObserver::class);
        Service::observe(ContentPerformanceObserver::class);
        Project::observe(ContentPerformanceObserver::class);
        Category::observe(ContentPerformanceObserver::class);
        Brand::observe(ContentPerformanceObserver::class);
        Media::observe(ContentPerformanceObserver::class);
    }
}
