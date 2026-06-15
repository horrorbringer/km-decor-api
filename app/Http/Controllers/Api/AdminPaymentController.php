<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPaymentController extends Controller
{
    public function confirm(ReviewPaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->ensureAdmin($request);

        $payment = DB::transaction(function () use ($request, $payment) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (! in_array($payment->status, ['pending', 'submitted'], true)) {
                throw ValidationException::withMessages(['payment' => ['This payment cannot be confirmed.']]);
            }

            $payment->update([
                'status' => 'confirmed',
                'admin_notes' => $request->validated('admin_notes'),
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);
            $payment->order()->update(['payment_status' => 'paid']);

            return $payment;
        });

        return response()->json([
            'data' => new PaymentResource($payment),
            'message' => 'Payment confirmed.',
        ]);
    }

    public function reject(ReviewPaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->ensureAdmin($request);

        $payment = DB::transaction(function () use ($request, $payment) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (! in_array($payment->status, ['pending', 'submitted'], true)) {
                throw ValidationException::withMessages(['payment' => ['This payment cannot be rejected.']]);
            }

            $payment->update([
                'status' => 'rejected',
                'admin_notes' => $request->validated('admin_notes'),
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);
            $payment->order()->update(['payment_status' => 'unpaid']);

            return $payment;
        });

        return response()->json([
            'data' => new PaymentResource($payment),
            'message' => 'Payment rejected.',
        ]);
    }

    private function ensureAdmin(ReviewPaymentRequest $request): void
    {
        abort_unless(in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
    }
}
