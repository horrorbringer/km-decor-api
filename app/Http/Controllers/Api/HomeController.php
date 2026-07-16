<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ServiceResource;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Support\PerformanceCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $data = Cache::remember(PerformanceCache::HOMEPAGE, now()->addMinutes(5), function (): array {
            $products = Product::query()
                ->published()
                ->where('is_featured', true)
                ->with(['category', 'brand', 'images'])
                ->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->limit(8)
                ->get();

            $services = Service::query()
                ->where('is_active', true)
                ->where('is_featured', true)
                ->with('category')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(4)
                ->get();

            $projects = Project::query()
                ->published()
                ->where('is_featured', true)
                ->with(['services', 'products.category', 'products.brand', 'products.images'])
                ->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->limit(3)
                ->get();

            $brands = Brand::query()
                ->where('is_active', true)
                ->where('is_featured', true)
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(8)
                ->get();

            return [
                'featured_products' => ProductResource::collection($products)->resolve(),
                'featured_services' => ServiceResource::collection($services)->resolve(),
                'featured_projects' => ProjectResource::collection($projects)->resolve(),
                'featured_brands' => BrandResource::collection($brands)->resolve(),
            ];
        });

        return response()->json(['data' => $data]);
    }
}
