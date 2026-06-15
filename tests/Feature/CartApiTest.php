<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_can_view_empty_cart(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.item_count', 0)
            ->assertJsonPath('data.subtotal', 0);
    }

    public function test_customer_can_add_product_and_server_controls_price(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = $this->createProduct(['price' => 12.50, 'stock_qty' => 20]);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 0.01,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.subtotal', 25)
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.unit_price', 12.5)
            ->assertJsonPath('data.items.0.line_total', 25);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 12.50,
        ]);
    }

    public function test_adding_existing_product_increments_quantity(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = $this->createProduct(['stock_qty' => 20]);

        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', 5);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_customer_can_update_and_remove_owned_item(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = $this->createProduct(['stock_qty' => 20]);
        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2]);
        $item = $user->cart->items()->firstOrFail();

        $this->patchJson("/api/cart/items/{$item->id}", ['quantity' => 4])
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', 4);

        $this->deleteJson("/api/cart/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.items', []);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_cart_enforces_minimum_quantity_stock_and_publication(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = $this->createProduct(['min_order_qty' => 3, 'stock_qty' => 5]);
        $draft = $this->createProduct(['slug' => 'draft', 'sku' => 'DRAFT', 'status' => 'draft']);

        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 6])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson('/api/cart/items', ['product_id' => $draft->id, 'quantity' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('product_id');
    }

    public function test_customer_cannot_modify_another_customers_cart_item(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $product = $this->createProduct();
        $cart = Cart::create(['user_id' => $owner->id]);
        $item = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);
        Sanctum::actingAs($attacker);

        $this->patchJson("/api/cart/items/{$item->id}", ['quantity' => 3])->assertNotFound();
        $this->deleteJson("/api/cart/items/{$item->id}")->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_cart_requires_authentication(): void
    {
        $this->getJson('/api/cart')->assertUnauthorized();
        $this->postJson('/api/cart/items', [])->assertUnauthorized();
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'decor'],
            ['name' => 'Decor', 'type' => 'product'],
        );
        $brand = Brand::firstOrCreate(['slug' => 'kmd'], ['name' => 'KMD']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Decor Panel',
            'slug' => 'decor-panel',
            'sku' => 'DECOR-001',
            'short_description' => 'A useful decor product.',
            'price' => 10,
            'unit' => 'piece',
            'min_order_qty' => 1,
            'stock_qty' => 10,
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ], $overrides));
    }
}
