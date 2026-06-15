<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateInquiryRequest;
use App\Http\Resources\InquiryResource;
use App\Models\ServiceInquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminInquiryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:20'],
            'type' => ['nullable', 'string', 'max:30'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $inquiries = ServiceInquiry::query()
            ->with(['service', 'assignee'])
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($validated['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($validated['assigned_to'] ?? null, fn (Builder $query, int $user) => $query->where('assigned_to', $user))
            ->when($validated['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('project_name', 'like', "%{$search}%");
                });
            })
            ->latest('submitted_at')
            ->paginate($validated['per_page'] ?? 20);

        return InquiryResource::collection($inquiries);
    }

    public function show(ServiceInquiry $inquiry): InquiryResource
    {
        return new InquiryResource($inquiry->load(['service', 'assignee']));
    }

    public function update(UpdateInquiryRequest $request, ServiceInquiry $inquiry): InquiryResource
    {
        $data = $request->validated();
        $nextStatus = $data['status'] ?? $inquiry->status;

        if ($nextStatus === 'contacted' && $inquiry->contacted_at === null) {
            $data['contacted_at'] = now();
        }

        if (in_array($nextStatus, ['won', 'lost'], true)) {
            $data['closed_at'] = now();
        } elseif (array_key_exists('status', $data)) {
            $data['closed_at'] = null;
        }

        if ($nextStatus === 'quoted') {
            $data['replied_at'] = now();
        }

        $inquiry->update($data);

        return new InquiryResource($inquiry->load(['service', 'assignee']));
    }
}
