<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_can_checkout_cart_and_cart_is_cleared(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = $this->createProduct(['price' => 12.50, 'stock_qty' => 20]);
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1,
        ]);

        $response = $this->postJson('/api/checkout', $this->checkoutPayload());

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.subtotal', 25)
            ->assertJsonPath('data.total_amount', 25)
            ->assertJsonPath('data.items.0.unit_price', 12.5)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonStructure(['data' => ['id', 'order_number', 'customer', 'delivery', 'items'], 'message']);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'subtotal' => 25]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_guest_can_checkout_with_explicit_items(): void
    {
        $product = $this->createProduct(['price' => 8.75]);

        $response = $this->postJson('/api/checkout', [
            ...$this->checkoutPayload(['delivery_method' => 'pickup']),
            'area' => null,
            'address' => null,
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer.name', 'Sok Dara')
            ->assertJsonPath('data.delivery.method', 'pickup')
            ->assertJsonPath('data.subtotal', 26.25);

        $this->assertDatabaseHas('orders', ['user_id' => null, 'customer_phone' => '012345678']);
    }

    public function test_authenticated_customer_can_checkout_with_explicit_items_when_cart_is_empty(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = $this->createProduct(['price' => 8.50]);

        $response = $this->postJson('/api/checkout', [
            ...$this->checkoutPayload(),
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer.name', 'Sok Dara')
            ->assertJsonPath('data.subtotal', 8.5)
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 1);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'subtotal' => 8.5]);
    }

    public function test_checkout_rejects_empty_cart_and_stock_changes(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', $this->checkoutPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('items');

        $product = $this->createProduct(['stock_qty' => 1]);
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->price,
        ]);

        $this->postJson('/api/checkout', $this->checkoutPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_customer_can_list_and_view_only_owned_orders(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $owned = $this->createOrder($customer, 'KMD-OWNED');
        $other = $this->createOrder($otherCustomer, 'KMD-OTHER');
        Sanctum::actingAs($customer);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $owned->id);

        $this->getJson("/api/orders/{$owned->id}")
            ->assertOk()->assertJsonPath('data.order_number', 'KMD-OWNED');
        $this->getJson("/api/orders/{$other->id}")->assertNotFound();
    }

    public function test_guest_checkout_validates_items_and_delivery_fields(): void
    {
        $this->postJson('/api/checkout', [
            'name' => 'Sok Dara',
            'phone' => '012345678',
            'delivery_method' => 'delivery',
            'timing' => 'scheduled',
            'support' => 'none',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['area', 'address', 'preferred_date', 'items']);
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sok Dara',
            'phone' => '012345678',
            'email' => 'dara@example.com',
            'delivery_method' => 'delivery',
            'area' => 'Phnom Penh',
            'address' => 'House 12, Street 123, Phnom Penh',
            'timing' => 'standard',
            'support' => 'none',
            'notes' => 'Call before delivery.',
        ], $overrides);
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

    private function createOrder(User $user, string $number): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => $number,
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
