<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReorderController extends Controller
{
    public function __invoke(Request $request, string $order): JsonResponse
    {
        $result = DB::transaction(function () use ($request, $order) {
            $order = Order::query()
                ->whereKey($order)
                ->where('user_id', $request->user()->id)
                ->with('items')
                ->firstOrFail();
            $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
            $skipped = [];
            $added = 0;

            $products = Product::query()
                ->whereIn('id', $order->items->pluck('product_id')->filter())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($order->items as $orderItem) {
                $product = $products->get($orderItem->product_id);

                if (! $product || $product->status !== 'published' || ($product->published_at && $product->published_at->isFuture())) {
                    $skipped[] = $this->skipped($orderItem, 'unavailable');

                    continue;
                }

                $cartItem = $cart->items()->where('product_id', $product->id)->lockForUpdate()->first();
                $quantity = ($cartItem?->quantity ?? 0) + $orderItem->quantity;

                if ($quantity < $product->min_order_qty) {
                    $skipped[] = $this->skipped($orderItem, 'minimum_order_not_met');

                    continue;
                }

                if (! $product->allow_backorder && $quantity > $product->stock_qty) {
                    $skipped[] = $this->skipped($orderItem, 'insufficient_stock');

                    continue;
                }

                $cart->items()->updateOrCreate(
                    ['product_id' => $product->id],
                    ['quantity' => $quantity, 'unit_price' => $product->price],
                );
                $added++;
            }

            return [
                'cart' => $cart->load(['items.product.brand', 'items.product.images']),
                'added' => $added,
                'skipped' => $skipped,
            ];
        });

        return response()->json([
            'data' => [
                'cart' => new CartResource($result['cart']),
                'added_item_count' => $result['added'],
                'skipped_items' => $result['skipped'],
            ],
            'message' => $result['added'] > 0
                ? 'Available order items were added to your cart.'
                : 'No order items could be added to your cart.',
        ]);
    }

    private function skipped($orderItem, string $reason): array
    {
        return [
            'product_id' => $orderItem->product_id,
            'name' => $orderItem->product_name,
            'sku' => $orderItem->product_sku,
            'quantity' => $orderItem->quantity,
            'reason' => $reason,
        ];
    }
}
