<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request): JsonResponse
    {
        $query = $request->validated('q');
        $limit = $request->integer('limit', 5);
        $term = '%'.static::escapeLikeWildcard($query).'%';

        $products = Product::query()
            ->published()
            ->with(['brand:id,name,slug', 'category:id,name,slug', 'images'])
            ->where(function (Builder $builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('name_kh', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $term))
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', $term));
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'type' => 'product',
                'name' => $product->name,
                'name_kh' => $product->name_kh,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'currency' => $product->currency,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
                'image_url' => ($product->images->firstWhere('is_primary', true) ?? $product->images->first())?->image_url,
                'url' => "/products/{$product->slug}",
            ]);

        $services = Service::query()
            ->where('is_active', true)
            ->where(function (Builder $builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('name_kh', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhere('short_description_kh', 'like', $term);
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'type' => 'service',
                'name' => $service->name,
                'name_kh' => $service->name_kh,
                'slug' => $service->slug,
                'description' => $service->short_description,
                'image_url' => $service->image_url,
                'url' => "/services/{$service->slug}",
            ]);

        $categories = Category::query()
            ->where('is_active', true)
            ->where(function (Builder $builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('name_kh', 'like', $term)
                    ->orWhere('description', 'like', $term);
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'type' => 'category',
                'name' => $category->name,
                'name_kh' => $category->name_kh,
                'slug' => $category->slug,
                'category_type' => $category->type,
                'image_url' => $category->image_url,
                'url' => $category->type === 'service'
                    ? "/services?category={$category->slug}"
                    : "/products?category={$category->slug}",
            ]);

        $brands = Brand::query()
            ->where('is_active', true)
            ->where(function (Builder $builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('name_kh', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('description_kh', 'like', $term);
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get()
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'type' => 'brand',
                'name' => $brand->name,
                'name_kh' => $brand->name_kh,
                'slug' => $brand->slug,
                'logo_url' => $brand->logo_url,
                'url' => "/products?brand={$brand->slug}",
            ]);

        return response()->json([
            'data' => compact('products', 'services', 'categories', 'brands'),
            'meta' => [
                'query' => $query,
                'total' => $products->count() + $services->count() + $categories->count() + $brands->count(),
                'limit_per_group' => $limit,
            ],
        ]);
    }

    private static function escapeLikeWildcard(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
