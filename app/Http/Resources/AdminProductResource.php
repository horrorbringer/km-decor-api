<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'short_description_kh' => $this->short_description_kh,
            'description' => $this->description,
            'description_kh' => $this->description_kh,
            'customer_goal' => $this->customer_goal,
            'features' => $this->features ?? [],
            'applications' => $this->applications ?? [],
            'material_notes' => $this->material_notes ?? [],
            'lead_time' => $this->lead_time,
            'delivery_note' => $this->delivery_note,
            'compatible_product_slugs' => $this->compatible_product_slugs ?? [],
            'specifications' => $this->specifications ?? [],
            'tags' => $this->tags ?? [],
            'price' => (float) $this->price,
            'compare_price' => $this->compare_price !== null ? (float) $this->compare_price : null,
            'currency' => $this->currency,
            'unit' => $this->unit,
            'min_order_qty' => $this->min_order_qty,
            'stock_qty' => $this->stock_qty,
            'allow_backorder' => $this->allow_backorder,
            'requires_installation' => $this->requires_installation,
            'warranty_months' => $this->warranty_months,
            'avg_rating' => (float) $this->avg_rating,
            'review_count' => $this->review_count,
            'is_featured' => $this->is_featured,
            'is_new' => $this->is_new,
            'is_best_seller' => $this->is_best_seller,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'category' => new AdminCategoryResource($this->whenLoaded('category')),
            'brand' => new AdminBrandResource($this->whenLoaded('brand')),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'image_url' => $image->image_url,
                'alt_text' => $image->alt_text,
                'is_primary' => $image->is_primary,
                'sort_order' => $image->sort_order,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
