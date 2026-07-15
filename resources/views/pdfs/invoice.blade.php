<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header { border-bottom: 2px solid #1f2937; padding-bottom: 16px; margin-bottom: 24px; }
        .header h1 { font-size: 28px; margin: 0; color: #1f2937; }
        .header .company { font-size: 14px; color: #6b7280; margin-top: 4px; }
        .invoice-details { margin-bottom: 24px; }
        .invoice-details table { width: 100%; }
        .invoice-details td { vertical-align: top; padding: 4px 0; }
        .invoice-details .label { color: #6b7280; font-size: 11px; text-transform: uppercase; }
        .invoice-details .value { font-weight: 600; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.items th { background: #f3f4f6; text-align: left; padding: 10px 8px; font-size: 11px; text-transform: uppercase; color: #6b7280; }
        table.items td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; }
        table.items .text-right { text-align: right; }
        .totals { width: 300px; margin-left: auto; }
        .totals td { padding: 6px 0; }
        .totals .label { color: #6b7280; }
        .totals .value { text-align: right; }
        .totals .grand-total { font-size: 16px; font-weight: 700; border-top: 2px solid #1f2937; padding-top: 8px; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #6b7280; text-align: center; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        .badge-draft { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
        <div class="company">KM Decor</div>
    </div>

    <div class="invoice-details">
        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="label">Bill To</div>
                    <div class="value">{{ $order->customer_name }}</div>
                    @if ($order->customer_email)
                        <div style="color: #6b7280;">{{ $order->customer_email }}</div>
                    @endif
                    @if ($order->customer_phone)
                        <div style="color: #6b7280;">{{ $order->customer_phone }}</div>
                    @endif
                    @if ($order->delivery_address)
                        <div style="color: #6b7280; margin-top: 4px;">{{ $order->delivery_address }}</div>
                    @endif
                </td>
                <td style="width: 50%; text-align: right;">
                    <table style="width: 100%;">
                        <tr><td class="label">Invoice Number</td><td class="value" style="text-align: right;">{{ $invoice->invoice_number }}</td></tr>
                        <tr><td class="label">Order Number</td><td class="value" style="text-align: right;">{{ $order->order_number }}</td></tr>
                        <tr><td class="label">Issue Date</td><td style="text-align: right;">{{ $invoice->issued_at?->format('M d, Y') ?? now()->format('M d, Y') }}</td></tr>
                        @if ($invoice->due_at)
                            <tr><td class="label">Due Date</td><td style="text-align: right;">{{ $invoice->due_at->format('M d, Y') }}</td></tr>
                        @endif
                        <tr>
                            <td class="label">Status</td>
                            <td style="text-align: right;">
                                <span class="badge badge-{{ $invoice->status }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 50%;">Item</th>
                <th style="width: 12%;">SKU</th>
                <th style="width: 10%;" class="text-right">Qty</th>
                <th style="width: 14%;" class="text-right">Unit Price</th>
                <th style="width: 14%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->product_sku }}</td>
                    <td class="text-right">{{ number_format($item->quantity) }} {{ $item->product_unit }}</td>
                    <td class="text-right">${{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format((float) $item->total_price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #9ca3af;">No items</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">${{ number_format((float) $invoice->subtotal, 2) }}</td>
        </tr>
        @if ((float) $invoice->delivery_fee > 0)
            <tr>
                <td class="label">Delivery Fee</td>
                <td class="value">${{ number_format((float) $invoice->delivery_fee, 2) }}</td>
            </tr>
        @endif
        @if ((float) $invoice->tax_amount > 0)
            <tr>
                <td class="label">Tax ({{ $invoice->tax_rate }}%)</td>
                <td class="value">${{ number_format((float) $invoice->tax_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td class="label">Total</td>
            <td class="value">${{ number_format((float) $invoice->total_amount, 2) }}</td>
        </tr>
        @if ($invoice->paid_at)
            <tr>
                <td class="label" style="color: #059669;">Paid At</td>
                <td class="value" style="color: #059669;">{{ $invoice->paid_at->format('M d, Y') }}</td>
            </tr>
        @endif
    </table>

    @if ($invoice->notes)
        <div style="margin-top: 24px; padding: 12px; background: #f9fafb; border-radius: 4px;">
            <div class="label" style="margin-bottom: 4px;">Notes</div>
            <div>{{ $invoice->notes }}</div>
        </div>
    @endif

    <div class="footer">
        <p>KM Decor &mdash; Thank you for your business!</p>
    </div>
</body>
</html>
