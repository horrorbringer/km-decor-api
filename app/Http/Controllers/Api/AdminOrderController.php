<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAdminOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminOrderController extends Controller
{
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['ready_to_ship', 'cancelled'],
        'ready_to_ship' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => ['refunded'],
        'cancelled' => [],
        'refunded' => [],
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:30'],
            'payment_status' => ['nullable', 'string', 'max:20'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::query()
            ->with(['items', 'payments'])
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($validated['payment_status'] ?? null, fn (Builder $query, string $status) => $query->where('payment_status', $status))
            ->when($validated['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->latest('ordered_at')
            ->paginate($validated['per_page'] ?? 20);

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load([
            'items', 'payments', 'statusHistory.changedBy',
        ]));
    }

    public function update(UpdateAdminOrderRequest $request, Order $order): OrderResource
    {
        $order = DB::transaction(function () use ($request, $order) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $nextStatus = $request->validated('status');

            if ($nextStatus !== $order->status && ! in_array($nextStatus, self::TRANSITIONS[$order->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Order cannot move from {$order->status} to {$nextStatus}."],
                ]);
            }

            $previousStatus = $order->status;
            $deliveryFee = $request->validated('delivery_fee', $order->delivery_fee);
            $timestamps = match ($nextStatus) {
                'confirmed' => ['confirmed_at' => now()],
                'shipped' => ['shipped_at' => now()],
                'completed' => ['completed_at' => now()],
                'cancelled' => ['cancelled_at' => now()],
                default => [],
            };

            $order->update([
                'status' => $nextStatus,
                'delivery_fee' => $deliveryFee,
                'total_amount' => (float) $order->subtotal + (float) $deliveryFee,
                'admin_notes' => $request->validated('admin_notes', $order->admin_notes),
                'payment_status' => $nextStatus === 'refunded' ? 'refunded' : $order->payment_status,
                ...$timestamps,
            ]);

            if ($nextStatus !== $previousStatus) {
                $order->statusHistory()->create([
                    'changed_by' => $request->user()->id,
                    'from_status' => $previousStatus,
                    'to_status' => $nextStatus,
                    'notes' => $request->validated('admin_notes'),
                ]);
            }

            return $order->load(['items', 'payments', 'statusHistory.changedBy']);
        });

        return new OrderResource($order);
    }
}
