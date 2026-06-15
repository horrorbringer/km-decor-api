<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'order_number', 'customer_name', 'customer_phone', 'customer_email',
        'delivery_method', 'delivery_area', 'delivery_address', 'map_url', 'timing',
        'preferred_date', 'support_type', 'subtotal', 'delivery_fee', 'total_amount',
        'currency', 'status', 'payment_status', 'notes', 'admin_notes', 'ordered_at',
        'confirmed_at', 'shipped_at', 'completed_at', 'cancelled_at',
    ];

    protected $attributes = [
        'currency' => 'USD',
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at');
    }
}
