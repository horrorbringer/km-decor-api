<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminServiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $services = Service::query()->with('category')
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('name_kh', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->when(array_key_exists('active', $validated), fn ($query) => $query->where('is_active', $validated['active']))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate($validated['per_page'] ?? 20);

        return AdminServiceResource::collection($services);
    }

    public function store(Request $request): AdminServiceResource
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $service = Service::create($data);

        return new AdminServiceResource($service->load('category'));
    }

    public function show(Service $service): AdminServiceResource
    {
        return new AdminServiceResource($service->load('category'));
    }

    public function update(Request $request, Service $service): AdminServiceResource
    {
        $service->update($this->validated($request, $service));

        return new AdminServiceResource($service->refresh()->load('category'));
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, ?Service $service = null): array
    {
        $required = $service ? 'sometimes' : 'required';

        return $request->validate([
            'category_id' => ['nullable', 'uuid', Rule::exists('categories', 'id')->where(fn ($query) => $query->whereIn('type', ['service', 'both']))],
            'name' => [$required, 'string', 'max:255'],
            'name_kh' => ['nullable', 'string', 'max:255'],
            'slug' => [$service ? 'sometimes' : 'nullable', 'string', 'max:255', Rule::unique('services', 'slug')->ignore($service)],
            'short_description' => [$required, 'string', 'max:500'],
            'short_description_kh' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'description_kh' => ['nullable', 'string'],
            'inquiry_type' => ['sometimes', 'in:quote,consultation,site_visit'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'portfolio_images' => ['nullable', 'array', 'max:30'],
            'portfolio_images.*' => ['url', 'max:500'],
            'faqs' => ['nullable', 'array', 'max:30'],
            'faqs.*.question' => ['required', 'string', 'max:500'],
            'faqs.*.answer' => ['required', 'string'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);
    }
}
