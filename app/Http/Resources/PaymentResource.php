<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'method' => $this->method,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'transaction_ref' => $this->transaction_ref,
            'proof_url' => $this->proof_url,
            'customer_notes' => $this->customer_notes,
            'admin_notes' => $this->when($request->user()?->role !== 'customer', $this->admin_notes),
            'submitted_at' => $this->submitted_at,
            'verified_at' => $this->verified_at,
        ];
    }
}
