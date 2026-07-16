<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'name_kh', 'slug', 'sku',
        'short_description', 'short_description_kh', 'description', 'description_kh',
        'customer_goal', 'features', 'applications', 'material_notes',
        'lead_time', 'delivery_note', 'compatible_product_slugs',
        'specifications', 'tags', 'price', 'compare_price', 'currency', 'unit',
        'min_order_qty', 'stock_qty', 'allow_backorder', 'requires_installation',
        'warranty_months', 'avg_rating', 'review_count', 'is_featured', 'is_new',
        'is_best_seller', 'sort_order', 'status', 'published_at',
        'meta_title', 'meta_description', 'og_image', 'structured_data',
    ];

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'tags' => 'array',
            'features' => 'array',
            'applications' => 'array',
            'material_notes' => 'array',
            'compatible_product_slugs' => 'array',
            'structured_data' => 'array',
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'avg_rating' => 'decimal:2',
            'allow_backorder' => 'boolean',
            'requires_installation' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_best_seller' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn (Builder $query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function primaryImageUrl(): ?string
    {
        $mediaUrl = $this->getFirstMediaUrl('images');

        if ($mediaUrl !== '') {
            return $mediaUrl;
        }

        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();

        return ($images->firstWhere('is_primary', true) ?? $images->first())?->image_url;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(300)
                    ->height(300)
                    ->fit(Fit::Crop);
                $this->addMediaConversion('medium')
                    ->width(800)
                    ->height(600)
                    ->fit(Fit::Crop);
            });

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile();
    }
}
