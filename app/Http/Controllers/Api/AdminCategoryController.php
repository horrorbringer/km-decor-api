<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminCategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:product,service,both'],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $categories = Category::query()
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('name_kh', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when(array_key_exists('active', $validated), fn ($query) => $query->where('is_active', $validated['active']))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate($validated['per_page'] ?? 20);

        return AdminCategoryResource::collection($categories);
    }

    public function store(Request $request): AdminCategoryResource
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        return new AdminCategoryResource(Category::create($data));
    }

    public function show(Category $category): AdminCategoryResource
    {
        return new AdminCategoryResource($category);
    }

    public function update(Request $request, Category $category): AdminCategoryResource
    {
        $category->update($this->validated($request, $category));

        return new AdminCategoryResource($category->refresh());
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->children()->exists() || $category->products()->exists() || $category->services()->exists()) {
            return response()->json(['message' => 'Category cannot be deleted while it has related records.'], 409);
        }

        $category->delete();

        return response()->json(status: 204);
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $required = $category ? 'sometimes' : 'required';

        return $request->validate([
            'parent_id' => ['nullable', 'uuid', Rule::exists('categories', 'id'), Rule::notIn([$category?->id])],
            'name' => [$required, 'string', 'max:255'],
            'name_kh' => ['nullable', 'string', 'max:255'],
            'slug' => [$category ? 'sometimes' : 'nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category)],
            'description' => ['nullable', 'string'],
            'type' => [$category ? 'sometimes' : 'nullable', 'in:product,service,both'],
            'icon' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);
    }
}
