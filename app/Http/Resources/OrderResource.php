<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'customer' => [
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
                'email' => $this->customer_email,
            ],
            'delivery' => [
                'method' => $this->delivery_method,
                'area' => $this->delivery_area,
                'address' => $this->delivery_address,
                'map_url' => $this->map_url,
                'timing' => $this->timing,
                'preferred_date' => $this->preferred_date?->toDateString(),
                'support' => $this->support_type,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'item_count' => $this->whenLoaded('items', fn () => $this->items->sum('quantity')),
            'subtotal' => (float) $this->subtotal,
            'delivery_fee' => (float) $this->delivery_fee,
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'admin_notes' => $this->when($request->user()?->role !== 'customer', $this->admin_notes),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'ordered_at' => $this->ordered_at,
        ];
    }
}
