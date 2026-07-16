<?php

namespace App\Http\Resources;

use App\Support\RichContent;
use App\Support\StoredMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_kh' => $this->title_kh,
            'slug' => $this->slug,
            'overview' => $this->overview,
            'overview_html' => $this->overview,
            'overview_text' => RichContent::toText($this->overview),
            'setting' => $this->setting,
            'focus' => $this->focus,
            'goal' => $this->when($request->routeIs('portfolio.show'), $this->goal),
            'goal_html' => $this->when($request->routeIs('portfolio.show'), $this->goal),
            'goal_text' => $this->when($request->routeIs('portfolio.show'), RichContent::toText($this->goal)),
            'challenge' => $this->when($request->routeIs('portfolio.show'), $this->challenge),
            'challenge_html' => $this->when($request->routeIs('portfolio.show'), $this->challenge),
            'challenge_text' => $this->when($request->routeIs('portfolio.show'), RichContent::toText($this->challenge)),
            'response' => $this->when($request->routeIs('portfolio.show'), $this->response),
            'response_html' => $this->when($request->routeIs('portfolio.show'), $this->response),
            'response_text' => $this->when($request->routeIs('portfolio.show'), RichContent::toText($this->response)),
            'scope' => $this->when($request->routeIs('portfolio.show'), $this->scope ?? []),
            'outcomes' => $this->when($request->routeIs('portfolio.show'), $this->outcomes ?? []),
            'process' => $this->when($request->routeIs('portfolio.show'), $this->process ?? []),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'gallery' => $this->when($request->routeIs('portfolio.show'), $this->getMedia('gallery')->map(fn ($media) => [
                'title' => $media->name,
                'caption' => $media->getCustomProperty('caption', ''),
                'image_url' => $media->getUrl(),
                'thumb_url' => $media->getAvailableUrl(['thumb']),
            ])),
            'primary_image' => $this->getFirstMediaUrl('gallery'),
            'sort_order' => $this->sort_order,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'meta_title' => $this->when($request->routeIs('portfolio.show'), $this->meta_title),
            'meta_description' => $this->when($request->routeIs('portfolio.show'), $this->meta_description),
            'og_image' => $this->when($request->routeIs('portfolio.show'), StoredMediaUrl::from($this->og_image)),
            'structured_data' => $this->when($request->routeIs('portfolio.show'), $this->structured_data ?? []),
        ];
    }
}
