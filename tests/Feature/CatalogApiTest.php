<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_listing_returns_only_published_products_and_supports_filters(): void
    {
        $category = Category::create([
            'name' => 'Gypsum Board',
            'slug' => 'gypsum-board',
            'type' => 'product',
        ]);
        $otherCategory = Category::create([
            'name' => 'Smart Home',
            'slug' => 'smart-home',
            'type' => 'product',
        ]);
        $brand = Brand::create(['name' => 'Zeit', 'slug' => 'zeit']);

        $product = $this->createProduct($category, $brand, [
            'name' => 'Zeit Standard Board',
            'slug' => 'zeit-standard-board',
            'sku' => 'ZTG-001',
            'is_featured' => true,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'image_url' => 'https://example.com/board.jpg',
            'is_primary' => true,
        ]);

        $this->createProduct($otherCategory, $brand, [
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'sku' => 'HID-001',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/products?category=gypsum-board&featured=1&search=Zeit');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'zeit-standard-board')
            ->assertJsonPath('data.0.category.slug', 'gypsum-board')
            ->assertJsonPath('data.0.brand.slug', 'zeit')
            ->assertJsonPath('data.0.primary_image', 'https://example.com/board.jpg')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_product_detail_uses_slug_and_does_not_expose_drafts(): void
    {
        $category = Category::create(['name' => 'Decor', 'slug' => 'decor', 'type' => 'product']);
        $brand = Brand::create(['name' => 'KMD', 'slug' => 'kmd']);
        $product = $this->createProduct($category, $brand, ['slug' => 'decor-panel']);
        $draft = $this->createProduct($category, $brand, [
            'slug' => 'draft-panel',
            'sku' => 'DRAFT-001',
            'status' => 'draft',
        ]);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'decor-panel')
            ->assertJsonStructure(['data' => ['description', 'specifications', 'images']]);

        $this->getJson("/api/products/{$draft->slug}")->assertNotFound();
    }

    public function test_categories_brands_and_services_hide_inactive_records(): void
    {
        Category::create(['name' => 'Active', 'slug' => 'active', 'type' => 'product']);
        Category::create(['name' => 'Inactive', 'slug' => 'inactive', 'type' => 'product', 'is_active' => false]);
        Brand::create(['name' => 'Active Brand', 'slug' => 'active-brand']);
        Brand::create(['name' => 'Inactive Brand', 'slug' => 'inactive-brand', 'is_active' => false]);
        $service = Service::create([
            'name' => 'Ceiling Decor',
            'slug' => 'ceiling-decor',
            'short_description' => 'Ceiling design and installation.',
            'is_featured' => true,
        ]);
        Service::create([
            'name' => 'Hidden Service',
            'slug' => 'hidden-service',
            'short_description' => 'Hidden.',
            'is_active' => false,
        ]);

        $this->getJson('/api/categories')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/brands')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/services?featured=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $service->slug);
        $this->getJson('/api/services/hidden-service')->assertNotFound();
    }

    private function createProduct(Category $category, Brand $brand, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Decor Panel',
            'slug' => 'decor-panel-default',
            'sku' => 'DECOR-001',
            'short_description' => 'A useful decor product.',
            'description' => 'Full product description.',
            'specifications' => ['Size' => '1200mm'],
            'tags' => ['decor'],
            'price' => 10.50,
            'unit' => 'piece',
            'stock_qty' => 20,
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ], $overrides));
    }
}
