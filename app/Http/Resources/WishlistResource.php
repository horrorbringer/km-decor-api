<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $primaryImage = $product->relationLoaded('images')
            ? $product->images->firstWhere('is_primary', true) ?? $product->images->first()
            : null;

        return [
            'id' => $this->id,
            'saved_at' => $this->created_at,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'name_kh' => $product->name_kh,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
                'price' => (float) $product->price,
                'compare_price' => $product->compare_price !== null ? (float) $product->compare_price : null,
                'currency' => $product->currency,
                'unit' => $product->unit,
                'stock_status' => match (true) {
                    $product->stock_qty > 10 => 'in_stock',
                    $product->stock_qty > 0 => 'low_stock',
                    $product->allow_backorder => 'preorder',
                    default => 'out_of_stock',
                },
                'image_url' => $primaryImage?->image_url,
                'url' => "/products/{$product->slug}",
            ],
        ];
    }
}
