<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'featured' => ['nullable', 'boolean'],
            'in_stock' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,name'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()
            ->published()
            ->with(['category', 'brand', 'images'])
            ->when($validated['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('name_kh', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            })
            ->when($validated['category'] ?? null, fn (Builder $query, string $slug) => $query->whereHas('category', fn (Builder $query) => $query->where('slug', $slug)))
            ->when($validated['brand'] ?? null, fn (Builder $query, string $slug) => $query->whereHas('brand', fn (Builder $query) => $query->where('slug', $slug)))
            ->when(array_key_exists('featured', $validated), fn (Builder $query) => $query->where('is_featured', $validated['featured']))
            ->when(($validated['in_stock'] ?? false) === true, fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('stock_qty', '>', 0)->orWhere('allow_backorder', true)));

        match ($validated['sort'] ?? 'newest') {
            'price_asc' => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            'name' => $products->orderBy('name'),
            default => $products->orderByDesc('published_at')->orderBy('sort_order'),
        };

        return ProductResource::collection($products->paginate($validated['per_page'] ?? 20));
    }

    public function show(Product $product): ProductResource
    {
        abort_unless(
            $product->status === 'published' && ($product->published_at === null || $product->published_at->isPast()),
            404,
        );

        return new ProductResource($product->load(['category', 'brand', 'images']));
    }
}
