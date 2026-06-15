<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'label', 'recipient_name', 'recipient_phone', 'street_address',
        'sangkat', 'khan', 'city', 'province', 'postal_code', 'country',
        'latitude', 'longitude', 'map_url', 'delivery_notes', 'is_default', 'is_active',
    ];

    protected $attributes = [
        'country' => 'Cambodia',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
