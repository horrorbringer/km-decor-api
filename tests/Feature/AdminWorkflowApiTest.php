<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ServiceInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminWorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_manager_can_filter_and_view_order_queue(): void
    {
        $manager = User::factory()->create(['role' => 'order_manager']);
        $matching = $this->createOrder(['status' => 'confirmed', 'customer_name' => 'Sok Dara']);
        $this->createOrder(['status' => 'pending', 'customer_name' => 'Other Customer']);
        Sanctum::actingAs($manager);

        $this->getJson('/api/admin/orders?status=confirmed&search=Dara')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id);

        $this->getJson("/api/admin/orders/{$matching->id}")
            ->assertOk()
            ->assertJsonPath('data.customer.name', 'Sok Dara');
    }

    public function test_order_status_transition_updates_totals_timestamps_and_history(): void
    {
        $manager = User::factory()->create(['role' => 'order_manager']);
        $order = $this->createOrder(['status' => 'pending', 'subtotal' => 100, 'total_amount' => 100]);
        Sanctum::actingAs($manager);

        $this->patchJson("/api/admin/orders/{$order->id}", [
            'status' => 'confirmed',
            'delivery_fee' => 7.50,
            'admin_notes' => 'Stock and delivery confirmed.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.delivery_fee', 7.5)
            ->assertJsonPath('data.total_amount', 107.5)
            ->assertJsonPath('data.status_history.0.from_status', 'pending')
            ->assertJsonPath('data.status_history.0.to_status', 'confirmed');

        $this->assertNotNull($order->fresh()->confirmed_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'changed_by' => $manager->id,
            'from_status' => 'pending',
            'to_status' => 'confirmed',
        ]);
    }

    public function test_invalid_order_transition_is_rejected_without_history(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $order = $this->createOrder(['status' => 'pending']);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'shipped'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_sales_staff_can_manage_inquiry_pipeline_and_assignment(): void
    {
        $sales = User::factory()->create(['role' => 'sales_staff']);
        $inquiry = ServiceInquiry::create([
            'type' => 'service',
            'name' => 'Chan Vuthy',
            'phone' => '012345678',
            'message' => 'Need an office partition quote.',
            'submitted_at' => now(),
        ]);
        Sanctum::actingAs($sales);

        $this->getJson('/api/admin/inquiries?status=new&search=Vuthy')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inquiry->id);

        $this->patchJson("/api/admin/inquiries/{$inquiry->id}", [
            'status' => 'contacted',
            'assigned_to' => $sales->id,
            'admin_notes' => 'Customer requested a site visit.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'contacted')
            ->assertJsonPath('data.assignee.id', $sales->id);

        $this->assertNotNull($inquiry->fresh()->contacted_at);

        $this->patchJson("/api/admin/inquiries/{$inquiry->id}", [
            'status' => 'quoted',
            'quoted_price' => 1250,
        ])->assertOk()
            ->assertJsonPath('data.status', 'quoted')
            ->assertJsonPath('data.quoted_price', 1250);

        $this->assertNotNull($inquiry->fresh()->replied_at);
    }

    public function test_won_and_lost_inquiries_are_closed(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $inquiry = ServiceInquiry::create([
            'type' => 'contact',
            'name' => 'Lead Customer',
            'email' => 'lead@example.com',
            'message' => 'Please contact me.',
            'submitted_at' => now(),
        ]);

        $this->patchJson("/api/admin/inquiries/{$inquiry->id}", ['status' => 'won'])
            ->assertOk()->assertJsonPath('data.status', 'won');

        $this->assertNotNull($inquiry->fresh()->closed_at);
    }

    public function test_customer_and_wrong_staff_roles_cannot_access_admin_workflows(): void
    {
        $order = $this->createOrder();
        $inquiry = ServiceInquiry::create([
            'name' => 'Lead',
            'phone' => '012345678',
            'message' => 'Lead message.',
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'customer']));
        $this->getJson('/api/admin/orders')->assertForbidden();
        $this->getJson('/api/admin/inquiries')->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'sales_staff']));
        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'confirmed'])->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'order_manager']));
        $this->patchJson("/api/admin/inquiries/{$inquiry->id}", ['status' => 'contacted'])->assertForbidden();
    }

    public function test_inquiry_cannot_be_assigned_to_customer(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $customer = User::factory()->create(['role' => 'customer']);
        $inquiry = ServiceInquiry::create([
            'name' => 'Lead',
            'phone' => '012345678',
            'message' => 'Lead message.',
            'submitted_at' => now(),
        ]);

        $this->patchJson("/api/admin/inquiries/{$inquiry->id}", [
            'assigned_to' => $customer->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('assigned_to');
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
}
