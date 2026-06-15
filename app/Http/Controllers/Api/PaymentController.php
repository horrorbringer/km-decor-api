<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, string $order): JsonResponse
    {
        $payment = DB::transaction(function () use ($request, $order) {
            $order = Order::query()
                ->whereKey($order)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($order->status, ['completed', 'cancelled', 'refunded'], true)) {
                throw ValidationException::withMessages([
                    'order' => ['Payment cannot be submitted for this order.'],
                ]);
            }

            if ($order->payment_status === 'paid') {
                throw ValidationException::withMessages(['order' => ['This order is already paid.']]);
            }

            $hasOpenPayment = $order->payments()
                ->whereIn('status', ['pending', 'submitted', 'confirmed'])
                ->exists();

            if ($hasOpenPayment) {
                throw ValidationException::withMessages([
                    'order' => ['This order already has an active payment submission.'],
                ]);
            }

            $method = $request->validated('method');
            $payment = $order->payments()->create([
                'method' => $method,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => $method === 'bank_transfer' ? 'submitted' : 'pending',
                'transaction_ref' => $request->validated('transaction_ref'),
                'proof_url' => $request->validated('proof_url'),
                'customer_notes' => $request->validated('notes'),
                'submitted_at' => now(),
            ]);

            $order->update(['payment_status' => 'pending']);

            return $payment;
        });

        return (new PaymentResource($payment))
            ->additional(['message' => 'Payment details submitted for confirmation.'])
            ->response()
            ->setStatusCode(201);
    }
}
