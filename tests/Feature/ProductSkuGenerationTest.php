<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSkuGenerationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Test Products',
            'slug' => 'test-products',
            'type' => 'product',
        ]);
    }

    public function test_product_generates_a_unique_sku_when_it_is_not_provided(): void
    {
        $first = Product::create($this->productData('Ceiling Line'));
        $second = Product::create($this->productData('Ceiling Line', 'ceiling-line-two'));

        $this->assertMatchesRegularExpression('/^CEI-[A-Z0-9]{8}$/', $first->sku);
        $this->assertMatchesRegularExpression('/^CEI-[A-Z0-9]{8}$/', $second->sku);
        $this->assertNotSame($first->sku, $second->sku);
    }

    public function test_product_preserves_an_explicit_sku(): void
    {
        $product = Product::create([
            ...$this->productData('Imported Product'),
            'sku' => 'IMPORT-001',
        ]);

        $this->assertSame('IMPORT-001', $product->sku);
    }

    public function test_generated_sku_does_not_change_when_product_is_edited(): void
    {
        $product = Product::create($this->productData('Original Name'));
        $sku = $product->sku;

        $product->update(['name' => 'Updated Name']);

        $this->assertSame($sku, $product->fresh()->sku);
    }

    private function productData(string $name, string $slug = 'ceiling-line'): array
    {
        return [
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => $slug,
            'short_description' => 'Test product.',
            'price' => 1,
            'currency' => 'USD',
            'unit' => 'piece',
            'min_order_qty' => 1,
            'stock_qty' => 0,
            'status' => 'draft',
        ];
    }
}
