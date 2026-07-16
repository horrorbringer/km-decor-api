<?php

namespace App\Models;

use App\Notifications\OrderStatusChanged;
use App\Services\InvoiceService;
use App\Support\PerformanceCache;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

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

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public static function generateNumber(): string
    {
        return 'KMD-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    protected static function booted(): void
    {
        static::saved(fn () => PerformanceCache::forgetOrders());
        static::deleted(fn () => PerformanceCache::forgetOrders());

        static::updated(function (Order $order) {
            if ($order->wasChanged('status')) {
                $oldStatus = $order->getOriginal('status');
                $newStatus = $order->status;

                if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                    try {
                        app(InvoiceService::class)->generateForOrder($order);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }

                $users = Permission::query()
                    ->where('name', 'view_orders')
                    ->where('guard_name', 'web')
                    ->exists()
                    ? User::permission('view_orders')->get()
                    : collect();

                Notification::send($users, new OrderStatusChanged(
                    order: $order,
                    oldStatus: $oldStatus,
                    newStatus: $newStatus,
                ));
            }
        });
    }
}
