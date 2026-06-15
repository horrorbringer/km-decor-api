<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_publish_product_with_images(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $categoryId = $this->postJson('/api/admin/categories', [
            'name' => 'Wall Panels',
            'type' => 'product',
            'is_featured' => true,
        ])->assertCreated()->assertJsonPath('data.slug', 'wall-panels')->json('data.id');

        $brandId = $this->postJson('/api/admin/brands', [
            'name' => 'KMD Decor',
            'country_of_origin' => 'Cambodia',
        ])->assertCreated()->assertJsonPath('data.slug', 'kmd-decor')->json('data.id');

        $productResponse = $this->postJson('/api/admin/products', [
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'name' => 'Acoustic Wall Panel',
            'sku' => 'AWP-001',
            'short_description' => 'Decorative acoustic wall treatment.',
            'price' => 25.50,
            'unit' => 'panel',
            'stock_qty' => 30,
            'status' => 'published',
            'images' => [
                ['image_url' => 'https://example.com/front.jpg'],
                ['image_url' => 'https://example.com/detail.jpg'],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'acoustic-wall-panel')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.images.0.is_primary', true)
            ->assertJsonCount(2, 'data.images');

        $productId = $productResponse->json('data.id');
        $this->assertNotNull(Product::find($productId)->published_at);

        $this->getJson('/api/products/acoustic-wall-panel')
            ->assertOk()
            ->assertJsonPath('data.primary_image', 'https://example.com/front.jpg');
    }

    public function test_admin_can_manage_primary_product_image_and_archive_product(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'super_admin']));
        $category = Category::create(['name' => 'Decor', 'slug' => 'decor', 'type' => 'product']);
        $product = $this->createProduct($category);

        $first = $this->postJson("/api/admin/products/{$product->id}/images", [
            'image_url' => 'https://example.com/first.jpg',
        ])->assertOk()->json('data.images.0.id');

        $secondResponse = $this->postJson("/api/admin/products/{$product->id}/images", [
            'image_url' => 'https://example.com/second.jpg',
            'is_primary' => true,
        ])->assertOk();
        $second = collect($secondResponse->json('data.images'))->firstWhere('image_url', 'https://example.com/second.jpg');

        $this->assertDatabaseHas('product_images', ['id' => $first, 'is_primary' => false]);
        $this->assertDatabaseHas('product_images', ['id' => $second['id'], 'is_primary' => true]);

        $this->patchJson("/api/admin/products/{$product->id}", ['status' => 'archived'])
            ->assertOk()->assertJsonPath('data.status', 'archived');

        $this->getJson("/api/products/{$product->slug}")->assertNotFound();
    }

    public function test_admin_can_manage_service_and_category_dependencies_are_protected(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $category = Category::create(['name' => 'Installation', 'slug' => 'installation', 'type' => 'service']);

        $service = $this->postJson('/api/admin/services', [
            'category_id' => $category->id,
            'name' => 'Office Partition Installation',
            'short_description' => 'Site measurement and partition installation.',
            'inquiry_type' => 'site_visit',
            'faqs' => [
                ['question' => 'Do you inspect the site?', 'answer' => 'Yes, before quoting.'],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'office-partition-installation')
            ->assertJsonPath('data.faqs.0.question', 'Do you inspect the site?')
            ->json('data');

        $this->getJson('/api/services/office-partition-installation')->assertOk();

        $this->deleteJson("/api/admin/categories/{$category->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Category cannot be deleted while it has related records.');

        $this->patchJson("/api/admin/services/{$service['id']}", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->getJson('/api/services/office-partition-installation')->assertNotFound();
    }

    public function test_admin_catalog_lists_support_filters(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $category = Category::create(['name' => 'Panels', 'slug' => 'panels', 'type' => 'product']);
        $brand = Brand::create(['name' => 'Visible Brand', 'slug' => 'visible-brand']);
        $this->createProduct($category, $brand, ['name' => 'Matching Panel', 'sku' => 'MATCH-001']);
        $this->createProduct($category, $brand, ['name' => 'Draft Panel', 'slug' => 'draft-panel', 'sku' => 'DRAFT-001', 'status' => 'draft']);

        $this->getJson('/api/admin/products?status=published&search=Matching')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'MATCH-001');
    }

    public function test_non_admin_cannot_manage_catalog(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'customer']));

        $this->getJson('/api/admin/products')->assertForbidden();
        $this->postJson('/api/admin/categories', [
            'name' => 'Unauthorized',
            'type' => 'product',
        ])->assertForbidden();
    }

    private function createProduct(Category $category, ?Brand $brand = null, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'brand_id' => $brand?->id,
            'name' => 'Decor Panel',
            'slug' => 'decor-panel',
            'sku' => 'DECOR-001',
            'short_description' => 'A useful decor product.',
            'price' => 10.50,
            'unit' => 'piece',
            'stock_qty' => 20,
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ], $overrides));
    }
}
