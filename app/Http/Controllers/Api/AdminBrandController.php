<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminBrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminBrandController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $brands = Brand::query()
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('name_kh', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->when(array_key_exists('active', $validated), fn ($query) => $query->where('is_active', $validated['active']))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate($validated['per_page'] ?? 20);

        return AdminBrandResource::collection($brands);
    }

    public function store(Request $request): AdminBrandResource
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        return new AdminBrandResource(Brand::create($data));
    }

    public function show(Brand $brand): AdminBrandResource
    {
        return new AdminBrandResource($brand);
    }

    public function update(Request $request, Brand $brand): AdminBrandResource
    {
        $brand->update($this->validated($request, $brand));

        return new AdminBrandResource($brand->refresh());
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, ?Brand $brand = null): array
    {
        $required = $brand ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand)],
            'name_kh' => ['nullable', 'string', 'max:255'],
            'slug' => [$brand ? 'sometimes' : 'nullable', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brand)],
            'description' => ['nullable', 'string'],
            'description_kh' => ['nullable', 'string'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'country_of_origin' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);
    }
}
