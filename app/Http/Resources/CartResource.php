<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotal = $this->items->sum(fn ($item) => (float) $item->unit_price * $item->quantity);

        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->items),
            'item_count' => $this->items->sum('quantity'),
            'subtotal' => round($subtotal, 2),
            'currency' => 'USD',
        ];
    }
}
