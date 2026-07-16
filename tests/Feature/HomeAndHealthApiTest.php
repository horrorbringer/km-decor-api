<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use Database\Seeders\KmdCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAndHealthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_endpoint_returns_backend_owned_homepage_sections(): void
    {
        $this->seed(KmdCatalogSeeder::class);

        Product::where('slug', 'eco-block-ceiling-board')->update(['is_featured' => false]);
        Service::where('slug', 'furniture')->update(['is_featured' => false]);
        Project::where('slug', 'workspace-fitout')->update(['is_featured' => false]);

        $response = $this->getJson('/api/home');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'featured_products',
                    'featured_services',
                    'featured_projects',
                    'featured_brands',
                ],
            ])
            ->assertJsonPath('data.featured_products.0.slug', 'gypsum-board');

        $this->assertNotContains(
            'eco-block-ceiling-board',
            collect($response->json('data.featured_products'))->pluck('slug')->all(),
        );
        $this->assertNotContains(
            'furniture',
            collect($response->json('data.featured_services'))->pluck('slug')->all(),
        );
        $this->assertNotContains(
            'workspace-fitout',
            collect($response->json('data.featured_projects'))->pluck('slug')->all(),
        );
    }

    public function test_health_endpoint_reports_database_status_without_auth(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonStructure(['app', 'environment', 'timestamp']);
    }
}
