<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InquiryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_contact_message(): void
    {
        $response = $this->postJson('/api/contact', [
            'name' => 'Sok Dara',
            'phone' => '012345678',
            'message' => 'Please contact me about a renovation project.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'new')
            ->assertJsonStructure(['data' => ['id', 'status'], 'message']);

        $this->assertDatabaseHas('service_inquiries', [
            'type' => 'contact',
            'name' => 'Sok Dara',
            'phone' => '012345678',
            'user_id' => null,
        ]);
    }

    public function test_guest_can_submit_service_inquiry(): void
    {
        $service = Service::create([
            'name' => 'Partition Installation',
            'slug' => 'partition-installation',
            'short_description' => 'Partition planning and installation.',
        ]);

        $this->postJson('/api/inquiries', [
            'service_id' => $service->id,
            'name' => 'Chan Vuthy',
            'email' => 'vuthy@example.com',
            'project_location' => 'Phnom Penh',
            'preferred_date' => now()->addWeek()->toDateString(),
            'message' => 'I need a quote for an office partition.',
        ])->assertCreated();

        $this->assertDatabaseHas('service_inquiries', [
            'service_id' => $service->id,
            'type' => 'service',
            'email' => 'vuthy@example.com',
        ]);
    }

    public function test_inquiry_requires_contact_details_and_message(): void
    {
        $this->postJson('/api/inquiries', ['name' => 'No Contact'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone', 'message']);
    }

    public function test_authenticated_submissions_are_linked_to_customer_account(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $contactId = $this->postJson('/api/contact', [
            'name' => $user->name,
            'email' => $user->email,
            'message' => 'Please contact me.',
        ])->assertCreated()->json('data.id');

        $inquiryId = $this->postJson('/api/inquiries', [
            'name' => $user->name,
            'phone' => $user->phone,
            'message' => 'I need a service quote.',
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('service_inquiries', ['id' => $contactId, 'user_id' => $user->id]);
        $this->assertDatabaseHas('service_inquiries', ['id' => $inquiryId, 'user_id' => $user->id]);
    }

    public function test_customer_can_filter_and_view_only_owned_inquiries(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $owned = $this->createInquiry($customer, [
            'type' => 'service',
            'status' => 'quoted',
            'quoted_price' => 1200,
            'admin_notes' => 'Internal margin calculation.',
        ]);
        $this->createInquiry($customer, ['type' => 'contact', 'status' => 'new']);
        $otherInquiry = $this->createInquiry($other, ['type' => 'service', 'status' => 'quoted']);
        Sanctum::actingAs($customer);

        $this->getJson('/api/inquiries?status=quoted&type=service')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $owned->id)
            ->assertJsonPath('data.0.quoted_price', 1200)
            ->assertJsonMissing(['admin_notes' => 'Internal margin calculation.']);

        $this->getJson("/api/inquiries/{$owned->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'quoted')
            ->assertJsonMissing(['admin_notes' => 'Internal margin calculation.']);
        $this->getJson("/api/inquiries/{$otherInquiry->id}")->assertNotFound();
    }

    public function test_inquiry_history_requires_authentication(): void
    {
        $this->getJson('/api/inquiries')->assertUnauthorized();
        $this->getJson('/api/inquiries/example')->assertUnauthorized();
    }

    private function createInquiry(User $user, array $overrides = []): ServiceInquiry
    {
        return ServiceInquiry::create(array_merge([
            'user_id' => $user->id,
            'type' => 'service',
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'message' => 'Customer inquiry.',
            'submitted_at' => now(),
        ], $overrides));
    }
}
