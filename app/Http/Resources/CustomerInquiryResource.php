<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerInquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'service' => $this->whenLoaded('service', fn () => $this->service ? [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'slug' => $this->service->slug,
            ] : null),
            'contact' => [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'company' => $this->company,
            ],
            'project' => [
                'name' => $this->project_name,
                'location' => $this->project_location,
                'size' => $this->project_size,
                'budget_range' => $this->budget_range,
                'preferred_date' => $this->preferred_date?->toDateString(),
            ],
            'message' => $this->message,
            'attachments' => $this->attachments ?? [],
            'quoted_price' => $this->quoted_price !== null ? (float) $this->quoted_price : null,
            'submitted_at' => $this->submitted_at,
            'contacted_at' => $this->contacted_at,
            'closed_at' => $this->closed_at,
        ];
    }
}
