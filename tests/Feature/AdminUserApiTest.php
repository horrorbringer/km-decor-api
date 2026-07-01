<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ServiceInquiry;
use App\Models\User;
use App\Notifications\VerifyEmailApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_customers_and_view_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'name' => 'Sok Dara',
            'email' => 'dara@example.com',
            'role' => 'customer',
        ]);
        User::factory()->create(['role' => 'sales_staff']);
        $this->createOrder($customer);
        ServiceInquiry::create([
            'user_id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'message' => 'Need a quotation.',
            'submitted_at' => now(),
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users?role=customer&search=Dara')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $customer->id)
            ->assertJsonPath('data.0.orders_count', 1)
            ->assertJsonPath('data.0.inquiries_count', 1);

        $this->getJson("/api/admin/users/{$customer->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.orders')
            ->assertJsonCount(1, 'data.inquiries');
    }

    public function test_admin_can_deactivate_customer_and_revoke_tokens(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->createToken('browser');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$customer->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $customer->id]);
        $this->postJson('/api/login', [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_admin_can_manually_verify_customer_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->unverified()->create(['role' => 'customer']);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$customer->id}", ['email_verified' => true])
            ->assertOk()
            ->assertJsonPath('data.email_verified', true);

        $this->assertNotNull($customer->fresh()->email_verified_at);
    }

    public function test_super_admin_can_create_staff_and_assign_roles(): void
    {
        Notification::fake();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        Sanctum::actingAs($superAdmin);

        $staff = $this->postJson('/api/admin/users', [
            'name' => 'Sales Agent',
            'email' => 'SALES@EXAMPLE.COM',
            'phone' => '012555888',
            'role' => 'sales_staff',
            'password' => 'staffpass1',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'sales@example.com')
            ->assertJsonPath('data.role', 'sales_staff')
            ->json('data');

        Notification::assertSentTo(User::find($staff['id']), VerifyEmailApi::class);

        $this->patchJson("/api/admin/users/{$staff['id']}", ['role' => 'order_manager'])
            ->assertOk()->assertJsonPath('data.role', 'order_manager');
    }

    public function test_regular_admin_cannot_manage_staff_or_assign_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'sales_staff']);
        $customer = User::factory()->create(['role' => 'customer']);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$staff->id}", ['is_active' => false])->assertForbidden();
        $this->patchJson("/api/admin/users/{$customer->id}", ['role' => 'sales_staff'])->assertForbidden();
        $this->postJson('/api/admin/users', [
            'name' => 'New Staff',
            'email' => 'staff@example.com',
            'role' => 'sales_staff',
            'password' => 'staffpass1',
        ])->assertForbidden();
    }

    public function test_super_admin_cannot_change_own_access_or_remove_last_super_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        Sanctum::actingAs($superAdmin);

        $this->patchJson("/api/admin/users/{$superAdmin->id}", ['is_active' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('account');
        $this->patchJson("/api/admin/users/{$superAdmin->id}", ['role' => 'admin'])
            ->assertUnprocessable()->assertJsonValidationErrors('account');
    }

    public function test_customer_cannot_access_user_administration(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'customer']));

        $this->getJson('/api/admin/users')->assertForbidden();
    }

    private function createOrder(User $user): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMD-USER-001',
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'delivery_method' => 'pickup',
            'delivery_area' => 'Phnom Penh',
            'timing' => 'standard',
            'support_type' => 'none',
            'subtotal' => 25,
            'delivery_fee' => 0,
            'total_amount' => 25,
            'ordered_at' => now(),
        ]);
    }
}
