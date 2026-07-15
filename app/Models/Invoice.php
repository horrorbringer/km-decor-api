<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'invoice_number', 'status', 'issued_at', 'due_at', 'paid_at',
        'subtotal', 'tax_rate', 'tax_amount', 'delivery_fee', 'total_amount',
        'currency', 'notes',
    ];

    protected $attributes = [
        'currency' => 'USD',
        'status' => 'draft',
        'tax_rate' => 0,
        'tax_amount' => 0,
        'delivery_fee' => 0,
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function generateNumber(): string
    {
        $prefix = 'INV-' . date('Y') . '-';
        $last = static::query()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->value('invoice_number');

        $next = $last ? (int) substr($last, -5) + 1 : 1;

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function renderPdf(): \Barryvdh\DomPDF\PDF
    {
        $order = $this->order->loadMissing(['items', 'user']);

        return Pdf::loadView('pdfs.invoice', [
            'invoice' => $this,
            'order' => $order,
        ]);
    }
}
