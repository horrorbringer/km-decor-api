<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasUuids;

    protected $fillable = [
        'category_id', 'name', 'name_kh', 'slug', 'short_description',
        'short_description_kh', 'description', 'description_kh', 'inquiry_type',
        'image_url', 'portfolio_images', 'faqs', 'sort_order', 'is_active', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'portfolio_images' => 'array',
            'faqs' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(ServiceInquiry::class);
    }
}
