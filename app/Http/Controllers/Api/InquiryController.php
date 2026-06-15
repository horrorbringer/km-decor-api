<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInquiryRequest;
use App\Models\ServiceInquiry;
use Illuminate\Http\JsonResponse;

class InquiryController extends Controller
{
    public function __invoke(StoreInquiryRequest $request): JsonResponse
    {
        $inquiry = ServiceInquiry::create([
            ...$request->validated(),
            'user_id' => $request->user('sanctum')?->id,
            'type' => 'service',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'data' => ['id' => $inquiry->id, 'status' => $inquiry->status],
            'message' => 'Your inquiry has been received.',
        ], 201);
    }
}
