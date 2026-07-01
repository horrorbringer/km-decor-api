<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');

        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($items),
            'item_count' => $this->whenLoaded('items', fn () => $items->sum('quantity')),
            'subtotal' => $this->whenLoaded('items', fn () => round($items->sum(fn ($item) => (float) $item->unit_price * $item->quantity), 2)),
            'currency' => 'USD',
        ];
    }
}
