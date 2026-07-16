<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');

        if (! $product) {
            return [
                'id' => $this->id,
                'product_id' => $this->product_id,
                'quantity' => $this->quantity,
                'unit_price' => (float) $this->unit_price,
                'line_total' => round((float) $this->unit_price * $this->quantity, 2),
            ];
        }

        return [
            'id' => $this->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'brand' => $product->relationLoaded('brand') ? $product->brand?->name : null,
            'unit' => $product->unit,
            'image_url' => $product->primaryImageUrl(),
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'line_total' => round((float) $this->unit_price * $this->quantity, 2),
            'stock_status' => match (true) {
                $product->stock_qty >= $this->quantity => 'in_stock',
                $product->allow_backorder => 'preorder',
                default => 'insufficient_stock',
            },
        ];
    }
}
