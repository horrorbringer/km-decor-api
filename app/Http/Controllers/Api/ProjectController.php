<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $projects = Project::query()
            ->published()
            ->with(['services', 'products'])
            ->when(array_key_exists('featured', $validated), fn ($query) => $query->where('is_featured', $validated['featured']))
            ->orderBy('sort_order')
            ->orderByDesc('published_at');

        return ProjectResource::collection($projects->paginate($validated['per_page'] ?? 20));
    }

    public function show(Project $project): ProjectResource
    {
        abort_unless(
            $project->status === 'published' && ($project->published_at === null || $project->published_at->isPast()),
            404,
        );

        return new ProjectResource($project->load(['services', 'products']));
    }
}
