<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Support\Collection;

class HomepageContentReadiness
{
    public function summary(): array
    {
        $sections = [
            $this->products(),
            $this->services(),
            $this->projects(),
            $this->brands(),
        ];

        return [
            'sections' => $sections,
            'issue_count' => collect($sections)->sum(fn (array $section): int => count($section['issues'])),
        ];
    }

    private function products(): array
    {
        $records = Product::query()
            ->published()
            ->where('is_featured', true)
            ->with(['images', 'media'])
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        return $this->section(
            label: 'Featured products',
            count: $records->count(),
            target: 4,
            issues: $records
                ->filter(fn (Product $product): bool => $product->images->isEmpty() && $product->getMedia('images')->isEmpty())
                ->map(fn (Product $product): string => "{$product->name} needs a product image.")
        );
    }

    private function services(): array
    {
        $records = Service::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with('media')
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        return $this->section(
            label: 'Featured services',
            count: $records->count(),
            target: 4,
            issues: $records
                ->filter(fn (Service $service): bool => blank($service->image_url) && $service->getMedia('images')->isEmpty())
                ->map(fn (Service $service): string => "{$service->name} needs a service image.")
        );
    }

    private function projects(): array
    {
        $records = Project::query()
            ->published()
            ->where('is_featured', true)
            ->with('media')
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        return $this->section(
            label: 'Featured projects',
            count: $records->count(),
            target: 3,
            issues: $records
                ->filter(fn (Project $project): bool => $project->getMedia('gallery')->isEmpty())
                ->map(fn (Project $project): string => "{$project->title} needs a gallery image.")
        );
    }

    private function brands(): array
    {
        $records = Brand::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with('media')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        return $this->section(
            label: 'Featured brands',
            count: $records->count(),
            target: 4,
            issues: $records
                ->filter(fn (Brand $brand): bool => blank($brand->logo_url) && $brand->getMedia('logo')->isEmpty())
                ->map(fn (Brand $brand): string => "{$brand->name} needs a logo.")
        );
    }

    private function section(string $label, int $count, int $target, Collection $issues): array
    {
        $missingCount = max(0, $target - $count);
        $allIssues = $issues->values();

        if ($missingCount > 0) {
            $allIssues->prepend("Add {$missingCount} more featured item" . ($missingCount === 1 ? '' : 's') . '.');
        }

        return [
            'label' => $label,
            'count' => $count,
            'target' => $target,
            'status' => $allIssues->isEmpty() ? 'ready' : 'needs_work',
            'issues' => $allIssues->take(5)->all(),
        ];
    }
}
