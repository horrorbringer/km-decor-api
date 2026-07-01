<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Service;
use Database\Seeders\KmdCatalogSeeder;
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
        $product = $this->createProduct($category, $brand, [
            'slug' => 'decor-panel',
            'description' => '<p>Full <strong>product</strong> description.</p>',
            'description_kh' => '<p>Khmer <strong>description</strong>.</p>',
            'customer_goal' => 'Help customers choose a suitable decor panel.',
            'features' => ['Easy to quote', 'Project-ready finish'],
            'applications' => ['Cabinet work', 'Feature wall'],
            'material_notes' => ['Confirm thickness before order'],
            'lead_time' => '2-4 days',
            'delivery_note' => 'Delivery by truck',
            'compatible_product_slugs' => ['draft-panel'],
        ]);
        $draft = $this->createProduct($category, $brand, [
            'slug' => 'draft-panel',
            'sku' => 'DRAFT-001',
            'status' => 'draft',
        ]);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'decor-panel')
            ->assertJsonPath('data.description_html', '<p>Full <strong>product</strong> description.</p>')
            ->assertJsonPath('data.description_text', 'Full product description.')
            ->assertJsonPath('data.description_kh_html', '<p>Khmer <strong>description</strong>.</p>')
            ->assertJsonPath('data.description_kh_text', 'Khmer description.')
            ->assertJsonPath('data.customer_goal', 'Help customers choose a suitable decor panel.')
            ->assertJsonPath('data.features.0', 'Easy to quote')
            ->assertJsonPath('data.applications.1', 'Feature wall')
            ->assertJsonPath('data.material_notes.0', 'Confirm thickness before order')
            ->assertJsonPath('data.lead_time', '2-4 days')
            ->assertJsonPath('data.delivery_note', 'Delivery by truck')
            ->assertJsonPath('data.compatible_product_slugs.0', 'draft-panel')
            ->assertJsonStructure(['data' => ['description', 'description_html', 'description_text', 'specifications', 'images']]);

        $this->getJson("/api/products/{$draft->slug}")->assertNotFound();
    }

    public function test_service_detail_returns_explicit_rich_html_and_plain_text_fields(): void
    {
        $service = Service::create([
            'name' => 'Finished Ceiling',
            'slug' => 'finished-ceiling',
            'short_description' => 'Ceiling design and installation.',
            'description' => '<p>Install <strong>clean ceiling</strong> details.</p>',
            'description_kh' => '<p>Khmer <strong>service</strong> details.</p>',
            'is_active' => true,
        ]);

        $this->getJson("/api/services/{$service->slug}")
            ->assertOk()
            ->assertJsonPath('data.description_html', '<p>Install <strong>clean ceiling</strong> details.</p>')
            ->assertJsonPath('data.description_text', 'Install clean ceiling details.')
            ->assertJsonPath('data.description_kh_html', '<p>Khmer <strong>service</strong> details.</p>')
            ->assertJsonPath('data.description_kh_text', 'Khmer service details.');
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

    public function test_kmd_catalog_seeder_exposes_storefront_catalog_with_backend_ids(): void
    {
        $this->seed(KmdCatalogSeeder::class);

        $this->assertDatabaseCount('categories', 9);
        $this->assertDatabaseCount('brands', 8);
        $this->assertDatabaseCount('products', 10);
        $this->assertDatabaseCount('services', 4);

        $response = $this->getJson('/api/products/gypsum-board');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'gypsum-board')
            ->assertJsonPath('data.sku', 'ZTG-STD-1220')
            ->assertJsonPath('data.category.slug', 'gypsum-board')
            ->assertJsonPath('data.brand.slug', 'zeit')
            ->assertJsonPath('data.stock_status', 'in_stock')
            ->assertJsonPath('data.primary_image', '/products/gypsum_board.webp');

        $this->assertNotSame('gypsum-board', $response->json('data.id'));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $response->json('data.id'),
        );
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
            'customer_goal' => null,
            'features' => [],
            'applications' => [],
            'material_notes' => [],
            'lead_time' => null,
            'delivery_note' => null,
            'compatible_product_slugs' => [],
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
