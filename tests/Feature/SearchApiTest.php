<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_grouped_active_and_published_results(): void
    {
        $category = Category::create([
            'name' => 'Gypsum Materials',
            'slug' => 'gypsum-materials',
            'type' => 'product',
        ]);
        $brand = Brand::create(['name' => 'Zeit Gypsum', 'slug' => 'zeit-gypsum']);
        $product = $this->createProduct($category, $brand, [
            'name' => 'Standard Gypsum Board',
            'slug' => 'standard-gypsum-board',
        ]);
        Service::create([
            'name' => 'Gypsum Ceiling Installation',
            'slug' => 'gypsum-ceiling-installation',
            'short_description' => 'Professional ceiling installation.',
        ]);

        $response = $this->getJson('/api/search?q=gypsum');

        $response->assertOk()
            ->assertJsonPath('data.products.0.id', $product->id)
            ->assertJsonPath('data.products.0.type', 'product')
            ->assertJsonPath('data.products.0.url', '/products/standard-gypsum-board')
            ->assertJsonPath('data.services.0.type', 'service')
            ->assertJsonPath('data.categories.0.slug', 'gypsum-materials')
            ->assertJsonPath('data.brands.0.slug', 'zeit-gypsum')
            ->assertJsonPath('meta.query', 'gypsum')
            ->assertJsonPath('meta.total', 4);
    }

    public function test_search_matches_khmer_names(): void
    {
        $category = Category::create([
            'name' => 'Ceiling',
            'name_kh' => 'ពិដាន',
            'slug' => 'ceiling',
            'type' => 'service',
        ]);
        Service::create([
            'category_id' => $category->id,
            'name' => 'Finished Ceiling',
            'name_kh' => 'សេវាពិដាន',
            'slug' => 'finished-ceiling',
            'short_description' => 'Ceiling service.',
            'short_description_kh' => 'សេវាតុបតែងពិដាន',
        ]);

        $this->getJson('/api/search?q='.urlencode('ពិដាន'))
            ->assertOk()
            ->assertJsonPath('data.services.0.slug', 'finished-ceiling')
            ->assertJsonPath('data.categories.0.slug', 'ceiling');
    }

    public function test_search_hides_draft_and_inactive_records(): void
    {
        $category = Category::create([
            'name' => 'Hidden Search Category',
            'slug' => 'hidden-category',
            'type' => 'product',
            'is_active' => false,
        ]);
        $activeCategory = Category::create([
            'name' => 'Visible Category',
            'slug' => 'visible-category',
            'type' => 'product',
        ]);
        $brand = Brand::create([
            'name' => 'Hidden Search Brand',
            'slug' => 'hidden-brand',
            'is_active' => false,
        ]);
        $activeBrand = Brand::create(['name' => 'Visible Brand', 'slug' => 'visible-brand']);
        $this->createProduct($activeCategory, $activeBrand, [
            'name' => 'Hidden Search Product',
            'slug' => 'hidden-product',
            'status' => 'draft',
        ]);
        Service::create([
            'name' => 'Hidden Search Service',
            'slug' => 'hidden-service',
            'short_description' => 'Hidden.',
            'is_active' => false,
        ]);

        $this->getJson('/api/search?q=hidden')
            ->assertOk()
            ->assertJsonPath('data.products', [])
            ->assertJsonPath('data.services', [])
            ->assertJsonPath('data.categories', [])
            ->assertJsonPath('data.brands', [])
            ->assertJsonPath('meta.total', 0);
    }

    public function test_search_matches_product_through_brand_and_respects_group_limit(): void
    {
        $category = Category::create(['name' => 'Boards', 'slug' => 'boards', 'type' => 'product']);
        $brand = Brand::create(['name' => 'Unique Supplier', 'slug' => 'unique-supplier']);

        foreach (range(1, 3) as $index) {
            $this->createProduct($category, $brand, [
                'name' => "Panel {$index}",
                'slug' => "panel-{$index}",
                'sku' => "PANEL-{$index}",
            ]);
        }

        $this->getJson('/api/search?q=unique&limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data.products')
            ->assertJsonPath('meta.limit_per_group', 2);
    }

    public function test_search_validates_query_and_limit(): void
    {
        $this->getJson('/api/search')->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson('/api/search?q=a')->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson('/api/search?q=%25%25&limit=20')
            ->assertUnprocessable()->assertJsonValidationErrors(['q', 'limit']);
    }

    private function createProduct(Category $category, Brand $brand, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Standard Board',
            'slug' => 'standard-board',
            'sku' => 'BOARD-001',
            'short_description' => 'A standard building board.',
            'price' => 10,
            'unit' => 'sheet',
            'stock_qty' => 10,
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ], $overrides));
    }
}
