<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'source' => $this->source,
            'status' => $this->status,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'service' => $this->whenLoaded('service', fn () => $this->service ? [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'slug' => $this->service->slug,
            ] : null),
            'project' => [
                'name' => $this->project_name,
                'location' => $this->project_location,
                'size' => $this->project_size,
                'budget_range' => $this->budget_range,
                'preferred_date' => $this->preferred_date?->toDateString(),
            ],
            'message' => $this->message,
            'attachments' => $this->attachments ?? [],
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'role' => $this->assignee->role,
            ] : null),
            'quoted_price' => $this->quoted_price !== null ? (float) $this->quoted_price : null,
            'admin_notes' => $this->admin_notes,
            'submitted_at' => $this->submitted_at,
            'contacted_at' => $this->contacted_at,
            'closed_at' => $this->closed_at,
        ];
    }
}
