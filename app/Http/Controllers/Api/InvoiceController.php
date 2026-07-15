<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $invoices = Invoice::query()
            ->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('order')
            ->latest('issued_at')
            ->paginate(20);

        return InvoiceResource::collection($invoices);
    }

    public function show(Request $request, Invoice $invoice): InvoiceResource
    {
        $this->authorizeAccess($request->user(), $invoice);

        return new InvoiceResource($invoice->loadMissing('order'));
    }

    public function download(Request $request, Invoice $invoice): mixed
    {
        $this->authorizeAccess($request->user(), $invoice);

        $pdf = $this->invoiceService->downloadPdf($invoice);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function generateFromOrder(Request $request, Order $order): InvoiceResource
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        if ($order->status !== 'completed') {
            return response()->json([
                'message' => 'Invoice can only be generated for completed orders.',
            ], 422);
        }

        $invoice = $this->invoiceService->generateForOrder($order);

        return new InvoiceResource($invoice->loadMissing('order'));
    }

    private function authorizeAccess($user, Invoice $invoice): void
    {
        if ($invoice->order->user_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }
    }
}
