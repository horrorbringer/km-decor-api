<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'icon' => $this->icon,
            'image_url' => $this->image_url,
            'is_featured' => $this->is_featured,
            'product_count' => $this->whenCounted('products'),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'og_image' => $this->og_image ? asset("storage/{$this->og_image}") : null,
            'structured_data' => $this->structured_data ?? [],
        ];
    }
}
