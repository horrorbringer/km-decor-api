<?php

namespace App\Http\Resources;

use App\Support\RichContent;
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
            'description_html' => $this->when($request->routeIs('services.show'), $this->description),
            'description_text' => $this->when($request->routeIs('services.show'), RichContent::toText($this->description)),
            'description_kh' => $this->when($request->routeIs('services.show'), $this->description_kh),
            'description_kh_html' => $this->when($request->routeIs('services.show'), $this->description_kh),
            'description_kh_text' => $this->when($request->routeIs('services.show'), RichContent::toText($this->description_kh)),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'inquiry_type' => $this->inquiry_type,
            'image_url' => $this->image_url,
            'portfolio_images' => $this->when($request->routeIs('services.show'), $this->portfolio_images ?? []),
            'faqs' => $this->when($request->routeIs('services.show'), $this->faqs ?? []),
            'is_active' => $this->is_active,
            'is_published' => $this->is_active,
            'is_featured' => $this->is_featured,
            'meta_title' => $this->when($request->routeIs('services.show'), $this->meta_title),
            'meta_description' => $this->when($request->routeIs('services.show'), $this->meta_description),
            'og_image' => $this->when($request->routeIs('services.show'), $this->og_image ? asset("storage/{$this->og_image}") : null),
            'structured_data' => $this->when($request->routeIs('services.show'), $this->structured_data ?? []),
        ];
    }
}
