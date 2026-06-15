<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function show(Request $request): CartResource
    {
        return new CartResource($this->loadCart($request));
    }

    public function store(StoreCartItemRequest $request): CartResource
    {
        DB::transaction(function () use ($request) {
            $product = Product::query()->lockForUpdate()->findOrFail($request->validated('product_id'));
            $this->ensureProductCanBePurchased($product);

            $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
            $item = $cart->items()->where('product_id', $product->id)->lockForUpdate()->first();
            $quantity = ($item?->quantity ?? 0) + $request->integer('quantity');
            $this->ensureQuantityIsAvailable($product, $quantity);

            $cart->items()->updateOrCreate(
                ['product_id' => $product->id],
                ['quantity' => $quantity, 'unit_price' => $product->price],
            );
        });

        return new CartResource($this->loadCart($request));
    }

    public function update(UpdateCartItemRequest $request, string $item): CartResource
    {
        DB::transaction(function () use ($request, $item) {
            $cartItem = $this->findOwnedItem($request, $item, true);
            $product = Product::query()->lockForUpdate()->findOrFail($cartItem->product_id);
            $this->ensureProductCanBePurchased($product);
            $this->ensureQuantityIsAvailable($product, $request->integer('quantity'));

            $cartItem->update([
                'quantity' => $request->integer('quantity'),
                'unit_price' => $product->price,
            ]);
        });

        return new CartResource($this->loadCart($request));
    }

    public function destroy(Request $request, string $item): CartResource
    {
        $this->findOwnedItem($request, $item)->delete();

        return new CartResource($this->loadCart($request));
    }

    private function loadCart(Request $request): Cart
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $cart->wasRecentlyCreated = false;

        return $cart->load(['items.product.brand', 'items.product.images']);
    }

    private function findOwnedItem(Request $request, string $item, bool $lock = false): CartItem
    {
        $query = CartItem::query()
            ->whereKey($item)
            ->whereHas('cart', fn ($query) => $query->where('user_id', $request->user()->id));

        return ($lock ? $query->lockForUpdate() : $query)->firstOrFail();
    }

    private function ensureProductCanBePurchased(Product $product): void
    {
        $isPublished = $product->status === 'published'
            && ($product->published_at === null || $product->published_at->isPast());

        if (! $isPublished) {
            throw ValidationException::withMessages([
                'product_id' => ['This product is not available for purchase.'],
            ]);
        }
    }

    private function ensureQuantityIsAvailable(Product $product, int $quantity): void
    {
        if ($quantity < $product->min_order_qty) {
            throw ValidationException::withMessages([
                'quantity' => ["The minimum order quantity is {$product->min_order_qty}."],
            ]);
        }

        if (! $product->allow_backorder && $quantity > $product->stock_qty) {
            throw ValidationException::withMessages([
                'quantity' => ["Only {$product->stock_qty} units are available."],
            ]);
        }
    }
}
