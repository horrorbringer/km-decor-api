<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerInquiryResource;
use App\Models\ServiceInquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerInquiryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:new,contacted,qualified,quoted,won,lost'],
            'type' => ['nullable', 'in:contact,service'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $inquiries = $request->user()->inquiries()
            ->with('service')
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($validated['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->latest('submitted_at')
            ->paginate($validated['per_page'] ?? 20);

        return CustomerInquiryResource::collection($inquiries);
    }

    public function show(Request $request, string $inquiry): CustomerInquiryResource
    {
        $inquiry = ServiceInquiry::query()
            ->whereKey($inquiry)
            ->where('user_id', $request->user()->id)
            ->with('service')
            ->firstOrFail();

        return new CustomerInquiryResource($inquiry);
    }
}
