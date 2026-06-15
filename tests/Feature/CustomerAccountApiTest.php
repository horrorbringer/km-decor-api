<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_profile_with_normalized_unique_email(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/profile', [
            'name' => 'Sok Dara Updated',
            'email' => ' NEW@EXAMPLE.COM ',
            'phone' => '012999888',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Sok Dara Updated')
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.phone', '012999888');

        $other = User::factory()->create();
        $this->patchJson('/api/profile', ['email' => $other->email])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_password_change_requires_current_password_and_revokes_all_tokens(): void
    {
        $user = User::factory()->create(['password' => Hash::make('oldpass123')]);
        $currentToken = $user->createToken('current')->plainTextToken;
        $user->createToken('other-device');

        $this->withToken($currentToken)->putJson('/api/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->withToken($currentToken)->putJson('/api/profile/password', [
            'current_password' => 'oldpass123',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertOk()->assertJsonPath('message', 'Password updated. Please log in again.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
        $this->app['auth']->forgetGuards();
        $this->withToken($currentToken)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_first_address_becomes_default_and_customer_can_add_another(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $first = $this->postJson('/api/addresses', $this->addressPayload(['label' => 'Home']))
            ->assertCreated()
            ->assertJsonPath('data.is_default', true)
            ->json('data.id');

        $second = $this->postJson('/api/addresses', $this->addressPayload([
            'label' => 'Office',
            'street_address' => 'Building 8, Street 271',
        ]))->assertCreated()
            ->assertJsonPath('data.is_default', false)
            ->json('data.id');

        $this->getJson('/api/addresses')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first);

        $this->assertNotSame($first, $second);
    }

    public function test_customer_can_change_default_address_and_cannot_unset_only_default(): void
    {
        $user = User::factory()->create();
        $home = $this->createAddress($user, ['label' => 'Home', 'is_default' => true]);
        $office = $this->createAddress($user, ['label' => 'Office']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/addresses/{$office->id}", ['is_default' => true])
            ->assertOk()->assertJsonPath('data.is_default', true);

        $this->assertFalse($home->fresh()->is_default);
        $this->assertTrue($office->fresh()->is_default);

        $this->patchJson("/api/addresses/{$office->id}", ['is_default' => false])
            ->assertOk()->assertJsonPath('data.is_default', true);
    }

    public function test_archiving_default_address_promotes_another_active_address(): void
    {
        $user = User::factory()->create();
        $home = $this->createAddress($user, ['label' => 'Home', 'is_default' => true]);
        $office = $this->createAddress($user, ['label' => 'Office']);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/addresses/{$home->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $office->id)
            ->assertJsonPath('data.0.is_default', true);

        $this->assertFalse($home->fresh()->is_active);
        $this->assertTrue($office->fresh()->is_default);
    }

    public function test_customer_cannot_update_or_delete_another_customers_address(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = $this->createAddress($owner, ['is_default' => true]);
        Sanctum::actingAs($other);

        $this->patchJson("/api/addresses/{$address->id}", ['label' => 'Stolen'])->assertNotFound();
        $this->deleteJson("/api/addresses/{$address->id}")->assertNotFound();
        $this->assertTrue($address->fresh()->is_active);
    }

    public function test_address_validation_pairs_coordinates_and_requires_delivery_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/addresses', [
            'label' => 'Incomplete',
            'latitude' => 11.5564,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'recipient_name', 'recipient_phone', 'street_address', 'city', 'province', 'longitude',
            ]);
    }

    public function test_account_endpoints_require_authentication(): void
    {
        $this->patchJson('/api/profile', [])->assertUnauthorized();
        $this->getJson('/api/addresses')->assertUnauthorized();
    }

    private function createAddress(User $user, array $overrides = []): Address
    {
        return Address::create(array_merge([
            'user_id' => $user->id,
            ...$this->addressPayload(),
        ], $overrides));
    }

    private function addressPayload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Home',
            'recipient_name' => 'Sok Dara',
            'recipient_phone' => '012345678',
            'street_address' => 'House 12, Street 123',
            'sangkat' => 'Boeung Keng Kang 1',
            'khan' => 'Boeung Keng Kang',
            'city' => 'Phnom Penh',
            'province' => 'Phnom Penh',
            'country' => 'Cambodia',
        ], $overrides);
    }
}
