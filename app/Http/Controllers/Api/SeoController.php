<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SeoController extends Controller
{
    public function sitemap()
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $sitemap = (new Sitemap())
            ->add(Url::create("{$baseUrl}/")->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)->setPriority(1.0))
            ->add(Url::create("{$baseUrl}/about")->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)->setPriority(0.8))
            ->add(Url::create("{$baseUrl}/services")->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setPriority(0.9))
            ->add(Url::create("{$baseUrl}/products")->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)->setPriority(0.9))
            ->add(Url::create("{$baseUrl}/blog")->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)->setPriority(0.8))
            ->add(Url::create("{$baseUrl}/contact")->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)->setPriority(0.6))
            ->add(Url::create("{$baseUrl}/search")->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)->setPriority(0.5));

        Product::query()
            ->published()
            ->select('id', 'slug', 'updated_at')
            ->chunkById(200, function ($products) use ($sitemap, $baseUrl) {
                foreach ($products as $product) {
                    $sitemap->add(Url::create("{$baseUrl}/products/{$product->slug}")
                        ->setLastModificationDate($product->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.8));
                }
            });

        Service::query()
            ->where('is_active', true)
            ->select('id', 'slug', 'updated_at')
            ->chunkById(200, function ($services) use ($sitemap, $baseUrl) {
                foreach ($services as $service) {
                    $sitemap->add(Url::create("{$baseUrl}/services/{$service->slug}")
                        ->setLastModificationDate($service->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.8));
                }
            });

        Category::query()
            ->where('is_active', true)
            ->select('id', 'slug', 'type', 'updated_at')
            ->chunkById(200, function ($categories) use ($sitemap, $baseUrl) {
                foreach ($categories as $category) {
                    $route = $category->type === 'service'
                        ? "{$baseUrl}/services?category={$category->slug}"
                        : "{$baseUrl}/products?category={$category->slug}";
                    $sitemap->add(Url::create($route)
                        ->setLastModificationDate($category->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.7));
                }
            });

        return response($sitemap->render(), 200)
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /cart/\n";
        $robots .= "Disallow: /checkout/\n";
        $robots .= "Disallow: /account/\n";
        $robots .= "Disallow: /login\n";
        $robots .= "Disallow: /register\n";
        $robots .= "Disallow: /verify-email\n";
        $robots .= "Disallow: /search\n\n";
        $robots .= "Sitemap: {$baseUrl}/sitemap.xml\n";

        return response($robots, 200)
            ->header('Content-Type', 'text/plain');
    }
}