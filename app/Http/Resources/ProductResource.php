<?php

namespace App\Http\Resources;

use App\Support\RichContent;
use App\Support\StoredMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mediaImages = $this->getMedia('images');
        $legacyImages = $this->relationLoaded('images') ? $this->images : collect();
        $images = $mediaImages->isNotEmpty()
            ? $mediaImages->values()->map(fn ($media, int $index): array => [
                'id' => (string) $media->id,
                'image_url' => $media->getUrl(),
                'url' => $media->getUrl(),
                'thumb_url' => $media->getAvailableUrl(['thumb']),
                'alt_text' => $media->getCustomProperty('alt_text', $this->name),
                'is_primary' => $index === 0,
                'sort_order' => $media->order_column,
            ])
            : $legacyImages->values()->map(fn ($image): array => [
                'id' => $image->id,
                'image_url' => $image->image_url,
                'url' => $image->image_url,
                'thumb_url' => $image->image_url,
                'alt_text' => $image->alt_text,
                'is_primary' => $image->is_primary,
                'sort_order' => $image->sort_order,
            ]);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'short_description_kh' => $this->short_description_kh,
            'description' => $this->when($request->routeIs('products.show'), $this->description),
            'description_html' => $this->when($request->routeIs('products.show'), $this->description),
            'description_text' => $this->when($request->routeIs('products.show'), RichContent::toText($this->description)),
            'description_kh' => $this->when($request->routeIs('products.show'), $this->description_kh),
            'description_kh_html' => $this->when($request->routeIs('products.show'), $this->description_kh),
            'description_kh_text' => $this->when($request->routeIs('products.show'), RichContent::toText($this->description_kh)),
            'customer_goal' => $this->when($request->routeIs('products.show'), $this->customer_goal),
            'features' => $this->when($request->routeIs('products.show'), $this->features ?? []),
            'applications' => $this->when($request->routeIs('products.show'), $this->applications ?? []),
            'material_notes' => $this->when($request->routeIs('products.show'), $this->material_notes ?? []),
            'lead_time' => $this->when($request->routeIs('products.show'), $this->lead_time),
            'delivery_note' => $this->when($request->routeIs('products.show'), $this->delivery_note),
            'compatible_product_slugs' => $this->when($request->routeIs('products.show'), $this->compatible_product_slugs ?? []),
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
            'primary_image' => $this->primaryImageUrl(),
            'images' => $images,
            'meta_title' => $this->when($request->routeIs('products.show'), $this->meta_title),
            'meta_description' => $this->when($request->routeIs('products.show'), $this->meta_description),
            'og_image' => $this->when($request->routeIs('products.show'), StoredMediaUrl::from($this->og_image)),
            'structured_data' => $this->when($request->routeIs('products.show'), $this->structured_data ?? []),
        ];
    }
}
