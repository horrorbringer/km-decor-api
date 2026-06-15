<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_bank_transfer_proof_and_server_controls_amount(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrder($customer, 125.50);
        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/orders/{$order->id}/payments", [
            'method' => 'bank_transfer',
            'amount' => 1,
            'transaction_ref' => 'ABA-12345',
            'proof_url' => 'https://example.com/payment-proof.jpg',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.method', 'bank_transfer')
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.amount', 125.5)
            ->assertJsonPath('data.transaction_ref', 'ABA-12345');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 125.50,
            'status' => 'submitted',
        ]);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_cash_on_delivery_does_not_require_proof(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrder($customer);
        Sanctum::actingAs($customer);

        $this->postJson("/api/orders/{$order->id}/payments", [
            'method' => 'cash_on_delivery',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.proof_url', null);
    }

    public function test_bank_transfer_requires_proof_and_active_submission_is_not_duplicated(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrder($customer);
        Sanctum::actingAs($customer);

        $this->postJson("/api/orders/{$order->id}/payments", ['method' => 'bank_transfer'])
            ->assertUnprocessable()->assertJsonValidationErrors('proof_url');

        $payload = [
            'method' => 'bank_transfer',
            'proof_url' => 'https://example.com/proof.jpg',
        ];
        $this->postJson("/api/orders/{$order->id}/payments", $payload)->assertCreated();
        $this->postJson("/api/orders/{$order->id}/payments", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('order');

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_customer_cannot_submit_payment_for_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->createOrder($owner);
        Sanctum::actingAs($other);

        $this->postJson("/api/orders/{$order->id}/payments", [
            'method' => 'cash_on_delivery',
        ])->assertNotFound();
    }

    public function test_admin_can_confirm_payment_and_customer_cannot(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrder($customer);
        $payment = $this->createPayment($order, 'submitted');

        Sanctum::actingAs($customer);
        $this->postJson("/api/admin/payments/{$payment->id}/confirm")->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/payments/{$payment->id}/confirm", [
            'admin_notes' => 'Matched ABA statement.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('message', 'Payment confirmed.');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'confirmed',
            'verified_by' => $admin->id,
        ]);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_admin_can_reject_payment_and_customer_can_resubmit(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrder($customer);
        $payment = $this->createPayment($order, 'submitted');
        $admin = User::factory()->create(['role' => 'super_admin']);

        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/payments/{$payment->id}/reject", [
            'admin_notes' => 'Proof is unreadable.',
        ])->assertOk()->assertJsonPath('data.status', 'rejected');

        $this->assertSame('unpaid', $order->fresh()->payment_status);

        Sanctum::actingAs($customer);
        $this->postJson("/api/orders/{$order->id}/payments", [
            'method' => 'bank_transfer',
            'proof_url' => 'https://example.com/clear-proof.jpg',
        ])->assertCreated();

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_order_detail_contains_payment_history(): void
    {
        $customer = User::factory()->create();
        $order = $this->createOrder($customer);
        $payment = $this->createPayment($order, 'submitted');
        Sanctum::actingAs($customer);

        $this->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.payments.0.id', $payment->id)
            ->assertJsonPath('data.payments.0.status', 'submitted');
    }

    private function createOrder(User $customer, float $total = 50): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'order_number' => 'KMD-'.fake()->unique()->numerify('########'),
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'delivery_method' => 'pickup',
            'timing' => 'standard',
            'support_type' => 'none',
            'subtotal' => $total,
            'delivery_fee' => 0,
            'total_amount' => $total,
            'ordered_at' => now(),
        ]);
    }

    private function createPayment(Order $order, string $status): Payment
    {
        $order->update(['payment_status' => 'pending']);

        return Payment::create([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => $order->total_amount,
            'status' => $status,
            'proof_url' => 'https://example.com/proof.jpg',
            'submitted_at' => now(),
        ]);
    }
}
