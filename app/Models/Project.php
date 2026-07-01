<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Project extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    protected $fillable = [
        'title', 'title_kh', 'slug', 'overview', 'setting', 'focus',
        'goal', 'challenge', 'response', 'scope', 'outcomes', 'process',
        'sort_order', 'is_featured', 'status', 'published_at',
        'meta_title', 'meta_description', 'og_image', 'structured_data',
    ];

    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'outcomes' => 'array',
            'process' => 'array',
            'structured_data' => 'array',
            'is_featured' => 'boolean',
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

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'project_service')
            ->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'project_product')
            ->withTimestamps();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(300)
                    ->height(300)
                    ->fit('crop');
                $this->addMediaConversion('medium')
                    ->width(800)
                    ->height(600)
                    ->fit('crop');
            });
    }
}
