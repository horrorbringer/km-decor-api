<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()
            ->with(['category', 'brand', 'images'])
            ->select('id', 'category_id', 'brand_id', 'name', 'name_kh', 'slug', 'sku', 'price', 'stock_qty', 'status', 'is_featured', 'sort_order', 'updated_at')
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('name_kh', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($validated['brand_id'] ?? null, fn ($query, $id) => $query->where('brand_id', $id))
            ->orderByDesc('updated_at')
            ->paginate($validated['per_page'] ?? 20);

        return AdminProductResource::collection($products);
    }

    public function store(Request $request): AdminProductResource
    {
        $data = $this->validated($request);
        $images = $data['images'] ?? [];
        unset($data['images']);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $this->setPublishingTimestamp($data);

        $product = DB::transaction(function () use ($data, $images) {
            $product = Product::create($data);

            foreach ($images as $index => $image) {
                $image['sort_order'] ??= $index;
                $product->images()->create($image);
            }

            $this->normalizePrimaryImage($product);

            return $product;
        });

        return new AdminProductResource($product->load(['category', 'brand', 'images']));
    }

    public function show(Product $product): AdminProductResource
    {
        return new AdminProductResource($product->load(['category', 'brand', 'images']));
    }

    public function update(Request $request, Product $product): AdminProductResource
    {
        $data = $this->validated($request, $product);
        unset($data['images']);
        $this->setPublishingTimestamp($data, $product);
        $product->update($data);

        return new AdminProductResource($product->refresh()->load(['category', 'brand', 'images']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(status: 204);
    }

    public function storeImage(Request $request, Product $product): AdminProductResource
    {
        $data = $request->validate([
            'image_url' => ['required', 'url', 'max:500'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($product, $data) {
            if ($data['is_primary'] ?? false) {
                $product->images()->update(['is_primary' => false]);
            }

            $product->images()->create($data);
            $this->normalizePrimaryImage($product);
        });

        return new AdminProductResource($product->refresh()->load(['category', 'brand', 'images']));
    }

    public function updateImage(Request $request, Product $product, ProductImage $image): AdminProductResource
    {
        abort_unless($image->product_id === $product->id, 404);
        $data = $request->validate([
            'image_url' => ['sometimes', 'required', 'url', 'max:500'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($product, $image, $data) {
            if ($data['is_primary'] ?? false) {
                $product->images()->whereKeyNot($image->id)->update(['is_primary' => false]);
            }

            $image->update($data);
            $this->normalizePrimaryImage($product);
        });

        return new AdminProductResource($product->refresh()->load(['category', 'brand', 'images']));
    }

    public function destroyImage(Product $product, ProductImage $image): JsonResponse
    {
        abort_unless($image->product_id === $product->id, 404);
        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $this->normalizePrimaryImage($product);
        }

        return response()->json(status: 204);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $required = $product ? 'sometimes' : 'required';

        return $request->validate([
            'category_id' => [$required, 'uuid', Rule::exists('categories', 'id')->where(fn ($query) => $query->whereIn('type', ['product', 'both']))],
            'brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
            'name' => [$required, 'string', 'max:255'],
            'name_kh' => ['nullable', 'string', 'max:255'],
            'slug' => [$product ? 'sometimes' : 'nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product)],
            'sku' => [$required, 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product)],
            'short_description' => [$required, 'string', 'max:500'],
            'short_description_kh' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'description_kh' => ['nullable', 'string'],
            'customer_goal' => ['nullable', 'string', 'max:1000'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['string', 'max:255'],
            'applications' => ['nullable', 'array', 'max:20'],
            'applications.*' => ['string', 'max:255'],
            'material_notes' => ['nullable', 'array', 'max:20'],
            'material_notes.*' => ['string', 'max:255'],
            'lead_time' => ['nullable', 'string', 'max:120'],
            'delivery_note' => ['nullable', 'string', 'max:160'],
            'compatible_product_slugs' => ['nullable', 'array', 'max:20'],
            'compatible_product_slugs.*' => ['string', 'max:255', 'exists:products,slug'],
            'specifications' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'price' => [$required, 'numeric', 'min:0', 'max:99999999.99'],
            'compare_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'unit' => [$required, 'string', 'max:50'],
            'min_order_qty' => ['sometimes', 'integer', 'min:1'],
            'stock_qty' => ['sometimes', 'integer', 'min:0'],
            'allow_backorder' => ['sometimes', 'boolean'],
            'requires_installation' => ['sometimes', 'boolean'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_new' => ['sometimes', 'boolean'],
            'is_best_seller' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'images' => [$product ? 'prohibited' : 'nullable', 'array', 'max:20'],
            'images.*.image_url' => ['required', 'url', 'max:500'],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'images.*.is_primary' => ['sometimes', 'boolean'],
            'images.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);
    }

    private function setPublishingTimestamp(array &$data, ?Product $product = null): void
    {
        if (($data['status'] ?? $product?->status ?? 'draft') === 'published'
            && ! array_key_exists('published_at', $data)
            && $product?->published_at === null) {
            $data['published_at'] = now();
        }
    }

    private function normalizePrimaryImage(Product $product): void
    {
        $images = ProductImage::query()
            ->where('product_id', $product->id)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        if ($images->isEmpty()) {
            return;
        }

        $primary = $images->firstWhere('is_primary', true) ?? $images->first();
        ProductImage::where('product_id', $product->id)->update(['is_primary' => false]);
        ProductImage::whereKey($primary->id)->update(['is_primary' => true]);
    }
}
