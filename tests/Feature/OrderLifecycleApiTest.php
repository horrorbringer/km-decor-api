<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderLifecycleApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── Cancellation ────────────────────────────────────────────────

    public function test_admin_can_cancel_pending_order_and_stock_is_restored(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct(['stock_qty' => 20]);
        $order = $this->createOrder(['status' => 'pending']);
        $this->createOrderItem($order, $product, 5);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertEquals(25, $product->fresh()->stock_qty);
        $this->assertNotNull($order->fresh()->cancelled_at);
    }

    public function test_admin_can_cancel_confirmed_order_and_stock_is_restored(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct(['stock_qty' => 15]);
        $order = $this->createOrder(['status' => 'confirmed']);
        $this->createOrderItem($order, $product, 10);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertEquals(25, $product->fresh()->stock_qty);
    }

    public function test_cancelled_order_does_not_restore_stock_for_backorder_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct(['stock_qty' => 0, 'allow_backorder' => true]);
        $order = $this->createOrder(['status' => 'pending']);
        $this->createOrderItem($order, $product, 5);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertOk();

        $this->assertEquals(0, $product->fresh()->stock_qty);
    }

    public function test_cancelling_ready_to_ship_order_does_not_restore_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct(['stock_qty' => 10]);
        $order = $this->createOrder(['status' => 'ready_to_ship']);
        $this->createOrderItem($order, $product, 5);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertOk();

        $this->assertEquals(10, $product->fresh()->stock_qty);
    }

    // ─── Refund ──────────────────────────────────────────────────────

    public function test_admin_can_refund_completed_order_and_payment_status_updates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder([
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'refunded'])
            ->assertOk()
            ->assertJsonPath('data.status', 'refunded')
            ->assertJsonPath('data.payment_status', 'refunded');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);
    }

    public function test_refund_is_only_allowed_from_completed_status(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $order = $this->createOrder(['status' => 'pending']);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'refunded'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    // ─── Full Lifecycle ──────────────────────────────────────────────

    public function test_order_can_progress_through_full_lifecycle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder(['status' => 'pending', 'subtotal' => 100, 'total_amount' => 100]);

        Sanctum::actingAs($admin);

        $transitions = [
            'confirmed' => ['delivery_fee' => 5],
            'processing' => [],
            'ready_to_ship' => [],
            'shipped' => [],
            'completed' => [],
        ];

        foreach ($transitions as $status => $extra) {
            $this->patchJson("/api/admin/orders/{$order->id}", array_merge(['status' => $status], $extra))
                ->assertOk()
                ->assertJsonPath('data.status', $status);

            $order->refresh();
            $this->assertSame($status, $order->status);
        }

        $this->assertDatabaseCount('order_status_histories', 5);
        $this->assertEquals(105, $order->fresh()->total_amount);
    }

    public function test_status_timestamps_are_set_at_correct_transitions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder(['status' => 'pending']);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'confirmed'])
            ->assertOk();
        $this->assertNotNull($order->fresh()->confirmed_at);
        $this->assertNull($order->fresh()->shipped_at);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'processing'])->assertOk();
        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'ready_to_ship'])->assertOk();

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'shipped'])
            ->assertOk();
        $this->assertNotNull($order->fresh()->shipped_at);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'completed'])
            ->assertOk();
        $this->assertNotNull($order->fresh()->completed_at);
    }

    // ─── Stock During Checkout ───────────────────────────────────────

    public function test_checkout_decrements_stock_for_each_product(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $productA = $this->createProduct(['stock_qty' => 20]);
        $productB = $this->createProduct([
            'slug' => 'panel-b',
            'sku' => 'PANEL-B',
            'stock_qty' => 15,
        ]);

        $cart = \App\Models\Cart::create(['user_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $productA->id,
            'quantity' => 3,
            'unit_price' => $productA->price,
        ]);
        $cart->items()->create([
            'product_id' => $productB->id,
            'quantity' => 5,
            'unit_price' => $productB->price,
        ]);

        $this->postJson('/api/checkout', [
            'name' => 'Sok Dara',
            'phone' => '012345678',
            'email' => 'dara@example.com',
            'delivery_method' => 'pickup',
            'timing' => 'standard',
            'support' => 'none',
        ])->assertCreated();

        $this->assertEquals(17, $productA->fresh()->stock_qty);
        $this->assertEquals(10, $productB->fresh()->stock_qty);
    }

    public function test_backorder_products_stock_is_not_decremented(): void
    {
        $product = $this->createProduct([
            'stock_qty' => 0,
            'allow_backorder' => true,
        ]);

        $this->postJson('/api/checkout', [
            'name' => 'Sok Dara',
            'phone' => '012345678',
            'email' => 'dara@example.com',
            'delivery_method' => 'pickup',
            'timing' => 'standard',
            'support' => 'none',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated();

        $this->assertEquals(0, $product->fresh()->stock_qty);
    }

    // ─── Order Number ────────────────────────────────────────────────

    public function test_order_numbers_are_unique(): void
    {
        $numbers = [];
        for ($i = 0; $i < 20; $i++) {
            $order = $this->createOrder();
            $numbers[] = $order->order_number;
        }

        $this->assertCount(20, array_unique($numbers));
    }

    public function test_order_number_has_expected_format(): void
    {
        $order = $this->createOrder();

        $this->assertMatchesRegularExpression('/^KMD-\d{8}$/', $order->order_number);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

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
            'allow_backorder' => false,
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ], $overrides));
    }

    private function createOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'KMD-'.fake()->unique()->numerify('########'),
            'customer_name' => 'Customer Name',
            'customer_phone' => '012345678',
            'customer_email' => 'customer@example.com',
            'delivery_method' => 'delivery',
            'delivery_area' => 'Phnom Penh',
            'delivery_address' => 'House 12, Street 123',
            'timing' => 'standard',
            'support_type' => 'none',
            'subtotal' => 50,
            'delivery_fee' => 0,
            'total_amount' => 50,
            'ordered_at' => now(),
        ], $overrides));
    }

    private function createOrderItem(Order $order, Product $product, int $quantity): OrderItem
    {
        return $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'product_unit' => $product->unit,
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'total_price' => $product->price * $quantity,
        ]);
    }
}
