<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishlistRequest;
use App\Http\Resources\WishlistResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class WishlistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return WishlistResource::collection($this->loadWishlist($request));
    }

    public function store(StoreWishlistRequest $request): WishlistResource
    {
        $product = Product::query()->findOrFail($request->validated('product_id'));

        if ($product->status !== 'published' || ($product->published_at && $product->published_at->isFuture())) {
            throw ValidationException::withMessages([
                'product_id' => ['This product is not available to save.'],
            ]);
        }

        $item = Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);
        $item->wasRecentlyCreated = false;

        return new WishlistResource($item->load(['product.brand', 'product.category', 'product.images', 'product.media']));
    }

    public function destroy(Request $request, string $product): AnonymousResourceCollection
    {
        Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product)
            ->firstOrFail()
            ->delete();

        return WishlistResource::collection($this->loadWishlist($request));
    }

    public function clear(Request $request): AnonymousResourceCollection
    {
        $request->user()->wishlistItems()->delete();

        return WishlistResource::collection(collect());
    }

    private function loadWishlist(Request $request)
    {
        return $request->user()->wishlistItems()
            ->whereHas('product', fn ($query) => $query->published())
            ->with(['product.brand', 'product.category', 'product.images', 'product.media'])
            ->latest()
            ->get();
    }
}
