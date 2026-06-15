<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WishlistAndReorderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_save_list_and_remove_wishlist_products(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        Sanctum::actingAs($user);

        $this->postJson('/api/wishlist', ['product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('data.product.id', $product->id)
            ->assertJsonPath('data.product.url', "/products/{$product->slug}");
        $this->postJson('/api/wishlist', ['product_id' => $product->id])->assertOk();

        $this->assertDatabaseCount('wishlists', 1);
        $this->getJson('/api/wishlist')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product.id', $product->id);

        $this->deleteJson("/api/wishlist/{$product->id}")
            ->assertOk()->assertJsonPath('data', []);
        $this->assertDatabaseCount('wishlists', 0);
    }

    public function test_wishlist_rejects_drafts_and_hides_products_that_later_become_unpublished(): void
    {
        $user = User::factory()->create();
        $draft = $this->createProduct(['status' => 'draft']);
        $product = $this->createProduct(['slug' => 'visible-product', 'sku' => 'VISIBLE']);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/wishlist', ['product_id' => $draft->id])
            ->assertUnprocessable()->assertJsonValidationErrors('product_id');

        $product->update(['status' => 'draft']);
        $this->getJson('/api/wishlist')->assertOk()->assertJsonPath('data', []);
    }

    public function test_customer_can_clear_only_their_wishlist(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->createProduct();
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);
        Wishlist::create(['user_id' => $other->id, 'product_id' => $product->id]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/wishlist')->assertOk()->assertJsonPath('data', []);

        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id]);
        $this->assertDatabaseHas('wishlists', ['user_id' => $other->id]);
    }

    public function test_reorder_adds_available_items_at_current_prices_and_merges_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(['price' => 15, 'stock_qty' => 20]);
        $order = $this->createOrder($user);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'product_unit' => $product->unit,
            'quantity' => 2,
            'unit_price' => 10,
            'total_price' => 20,
        ]);
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 9,
        ]);
        Sanctum::actingAs($user);

        $this->postJson("/api/orders/{$order->id}/reorder")
            ->assertOk()
            ->assertJsonPath('data.added_item_count', 1)
            ->assertJsonPath('data.skipped_items', [])
            ->assertJsonPath('data.cart.items.0.quantity', 3)
            ->assertJsonPath('data.cart.items.0.unit_price', 15)
            ->assertJsonPath('data.cart.subtotal', 45);
    }

    public function test_reorder_reports_unavailable_and_insufficient_stock_items(): void
    {
        $user = User::factory()->create();
        $unavailable = $this->createProduct(['status' => 'draft']);
        $lowStock = $this->createProduct(['slug' => 'low-stock', 'sku' => 'LOW', 'stock_qty' => 1]);
        $order = $this->createOrder($user);

        foreach ([[$unavailable, 1], [$lowStock, 3]] as [$product, $quantity]) {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_unit' => $product->unit,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'total_price' => (float) $product->price * $quantity,
            ]);
        }
        Sanctum::actingAs($user);

        $this->postJson("/api/orders/{$order->id}/reorder")
            ->assertOk()
            ->assertJsonPath('data.added_item_count', 0)
            ->assertJsonPath('data.skipped_items.0.reason', 'unavailable')
            ->assertJsonPath('data.skipped_items.1.reason', 'insufficient_stock')
            ->assertJsonPath('data.cart.items', []);
    }

    public function test_customer_cannot_reorder_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->createOrder($owner);
        Sanctum::actingAs($other);

        $this->postJson("/api/orders/{$order->id}/reorder")->assertNotFound();
    }

    public function test_wishlist_and_reorder_require_authentication(): void
    {
        $this->getJson('/api/wishlist')->assertUnauthorized();
        $this->postJson('/api/orders/example/reorder')->assertUnauthorized();
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'decor'], ['name' => 'Decor', 'type' => 'product']);
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

    private function createOrder(User $user): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMD-'.fake()->unique()->numerify('########'),
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'delivery_method' => 'pickup',
            'timing' => 'standard',
            'support_type' => 'none',
            'subtotal' => 10,
            'delivery_fee' => 0,
            'total_amount' => 10,
            'ordered_at' => now(),
        ]);
    }
}
