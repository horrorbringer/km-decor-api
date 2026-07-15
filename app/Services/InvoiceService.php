<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\PDF;

class InvoiceService
{
    public function generateForOrder(Order $order, ?float $taxRate = null): Invoice
    {
        if ($order->invoice) {
            return $order->invoice;
        }

        $subtotal = (float) $order->subtotal;
        $deliveryFee = (float) $order->delivery_fee;
        $taxRate = $taxRate ?? (float) config('invoices.tax_rate', 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $totalAmount = round($subtotal + $deliveryFee + $taxAmount, 2);

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => Invoice::generateNumber(),
            'status' => $order->payment_status === 'paid' ? 'paid' : 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays((int) config('invoices.due_days', 30)),
            'paid_at' => $order->payment_status === 'paid' ? $order->completed_at ?? now() : null,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            'currency' => $order->currency ?? 'USD',
        ]);

        return $invoice;
    }

    public function markAsPaid(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $invoice;
    }

    public function downloadPdf(Invoice $invoice): PDF
    {
        return $invoice->renderPdf();
    }
}
