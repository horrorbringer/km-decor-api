<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __invoke(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $requestedItems = $user
            ? $user->cart?->items()->get(['product_id', 'quantity']) ?? collect()
            : collect($request->validated('items'));

        if ($requestedItems->isEmpty()) {
            throw ValidationException::withMessages(['items' => ['The cart is empty.']]);
        }

        $order = DB::transaction(function () use ($request, $user, $requestedItems) {
            $products = Product::query()
                ->whereIn('id', $requestedItems->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = $this->buildLines($requestedItems, $products);
            $subtotal = $lines->sum('total_price');

            $order = Order::create([
                'user_id' => $user?->id,
                'order_number' => $this->orderNumber(),
                'customer_name' => $request->validated('name'),
                'customer_phone' => $request->validated('phone'),
                'customer_email' => $request->validated('email'),
                'delivery_method' => $request->validated('delivery_method'),
                'delivery_area' => $request->validated('area'),
                'delivery_address' => $request->validated('address'),
                'map_url' => $request->validated('map_url'),
                'timing' => $request->validated('timing'),
                'preferred_date' => $request->validated('preferred_date'),
                'support_type' => $request->validated('support'),
                'notes' => $request->validated('notes'),
                'subtotal' => $subtotal,
                'delivery_fee' => 0,
                'total_amount' => $subtotal,
                'ordered_at' => now(),
            ]);

            $order->items()->createMany($lines->all());
            $user?->cart?->items()->delete();

            return $order->load('items');
        });

        return (new OrderResource($order))
            ->additional(['message' => 'Order request submitted for confirmation.'])
            ->response()
            ->setStatusCode(201);
    }

    private function buildLines(Collection $requestedItems, Collection $products): Collection
    {
        return $requestedItems->map(function ($requestedItem) use ($products) {
            $productId = data_get($requestedItem, 'product_id');
            $quantity = (int) data_get($requestedItem, 'quantity');
            $product = $products->get($productId);

            if (! $product || $product->status !== 'published' || ($product->published_at && $product->published_at->isFuture())) {
                throw ValidationException::withMessages(['items' => ['One or more products are unavailable.']]);
            }

            if ($quantity < $product->min_order_qty) {
                throw ValidationException::withMessages([
                    'items' => ["{$product->name} requires at least {$product->min_order_qty} units."],
                ]);
            }

            if (! $product->allow_backorder && $quantity > $product->stock_qty) {
                throw ValidationException::withMessages([
                    'items' => ["Only {$product->stock_qty} units of {$product->name} are available."],
                ]);
            }

            $unitPrice = (float) $product->price;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_unit' => $product->unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => round($unitPrice * $quantity, 2),
            ];
        });
    }

    private function orderNumber(): string
    {
        return 'KMD-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
