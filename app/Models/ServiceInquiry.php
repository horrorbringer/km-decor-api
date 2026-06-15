<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceInquiry extends Model
{
    use HasUuids;

    protected $attributes = [
        'status' => 'new',
        'source' => 'website',
    ];

    protected $fillable = [
        'user_id', 'service_id', 'type', 'source', 'name', 'email', 'phone', 'company',
        'project_name', 'project_location', 'project_size', 'budget_range',
        'preferred_date', 'message', 'attachments', 'status', 'assigned_to',
        'admin_notes', 'quoted_price', 'replied_at', 'contacted_at', 'closed_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'attachments' => 'array',
            'submitted_at' => 'datetime',
            'replied_at' => 'datetime',
            'contacted_at' => 'datetime',
            'closed_at' => 'datetime',
            'quoted_price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
