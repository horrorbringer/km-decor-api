<?php

namespace App\Http\Resources;

use App\Support\StoredMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo_url' => $this->logo_url ?: $this->getFirstMediaUrl('logo'),
            'website_url' => $this->website_url,
            'country_of_origin' => $this->country_of_origin,
            'is_featured' => $this->is_featured,
            'product_count' => $this->whenCounted('products'),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'og_image' => StoredMediaUrl::from($this->og_image),
            'structured_data' => $this->structured_data ?? [],
        ];
    }
}
