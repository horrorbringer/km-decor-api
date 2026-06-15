<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primaryImage = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_primary', true) ?? $this->images->first()
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'short_description_kh' => $this->short_description_kh,
            'description' => $this->when($request->routeIs('products.show'), $this->description),
            'description_kh' => $this->when($request->routeIs('products.show'), $this->description_kh),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'price' => (float) $this->price,
            'compare_price' => $this->compare_price !== null ? (float) $this->compare_price : null,
            'currency' => $this->currency,
            'unit' => $this->unit,
            'min_order_qty' => $this->min_order_qty,
            'stock_qty' => $this->stock_qty,
            'stock_status' => match (true) {
                $this->stock_qty > 10 => 'in_stock',
                $this->stock_qty > 0 => 'low_stock',
                $this->allow_backorder => 'preorder',
                default => 'out_of_stock',
            },
            'requires_installation' => $this->requires_installation,
            'warranty_months' => $this->warranty_months,
            'rating' => (float) $this->avg_rating,
            'review_count' => $this->review_count,
            'badges' => array_values(array_filter([
                $this->is_new ? 'new' : null,
                $this->is_best_seller ? 'best_seller' : null,
                $this->is_featured ? 'featured' : null,
            ])),
            'specifications' => $this->when($request->routeIs('products.show'), $this->specifications ?? []),
            'tags' => $this->tags ?? [],
            'primary_image' => $primaryImage?->image_url,
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
