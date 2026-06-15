<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'short_description_kh' => $this->short_description_kh,
            'description' => $this->when($request->routeIs('services.show'), $this->description),
            'description_kh' => $this->when($request->routeIs('services.show'), $this->description_kh),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'inquiry_type' => $this->inquiry_type,
            'image_url' => $this->image_url,
            'portfolio_images' => $this->when($request->routeIs('services.show'), $this->portfolio_images ?? []),
            'faqs' => $this->when($request->routeIs('services.show'), $this->faqs ?? []),
            'is_featured' => $this->is_featured,
        ];
    }
}
