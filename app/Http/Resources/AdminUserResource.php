<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'email_verified' => $this->hasVerifiedEmail(),
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'orders_count' => $this->whenCounted('orders'),
            'inquiries_count' => $this->whenCounted('inquiries'),
            'addresses_count' => $this->whenCounted('addresses'),
            'wishlist_items_count' => $this->whenCounted('wishlistItems'),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'inquiries' => CustomerInquiryResource::collection($this->whenLoaded('inquiries')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
