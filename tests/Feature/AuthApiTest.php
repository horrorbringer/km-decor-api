<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailApi;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_receive_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Sok Dara',
            'email' => 'DARA@EXAMPLE.COM',
            'phone' => '012345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'device_name' => 'next-web',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'dara@example.com')
            ->assertJsonPath('data.user.role', 'customer')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['user', 'token', 'token_type']]);

        $this->assertDatabaseHas('users', [
            'email' => 'dara@example.com',
            'phone' => '012345678',
            'role' => 'customer',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        Notification::assertSentTo(User::where('email', 'dara@example.com')->first(), VerifyEmailApi::class);
    }

    public function test_customer_can_login_and_access_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'CUSTOMER@EXAMPLE.COM',
            'password' => 'secret123',
            'device_name' => 'browser',
        ])->assertOk();

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'customer@example.com');

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_rejects_invalid_credentials_and_inactive_accounts(): void
    {
        User::factory()->create([
            'email' => 'active@example.com',
            'password' => Hash::make('secret123'),
        ]);
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('secret123'),
            'is_active' => false,
        ]);

        $this->postJson('/api/login', [
            'email' => 'active@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'secret123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_customer_can_request_a_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->postJson('/api/forgot-password', [
            'email' => 'CUSTOMER@EXAMPLE.COM',
        ])->assertOk()->assertJsonPath(
            'message',
            'If an account exists for that email, a password reset link has been sent.'
        );

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_password_reset_request_does_not_reveal_unknown_accounts(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', [
            'email' => 'missing@example.com',
        ])->assertOk()->assertJsonPath(
            'message',
            'If an account exists for that email, a password reset link has been sent.'
        );

        Notification::assertNothingSent();
    }

    public function test_customer_can_reset_password_and_existing_tokens_are_revoked(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('old-password1'),
        ]);
        $user->createToken('browser');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => 'CUSTOMER@EXAMPLE.COM',
            'token' => $token,
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])->assertOk()->assertJsonPath('message', 'Password reset successfully.');

        $this->assertTrue(Hash::check('new-password1', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_password_reset_rejects_an_invalid_token(): void
    {
        User::factory()->create(['email' => 'customer@example.com']);

        $this->postJson('/api/reset-password', [
            'email' => 'customer@example.com',
            'token' => 'invalid-token',
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_customer_can_verify_email_with_a_signed_link(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['user' => $user->id, 'hash' => sha1($user->email)],
            false
        );

        $this->getJson($verificationUrl)
            ->assertOk()
            ->assertJsonPath('message', 'Email address verified successfully.');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_email_verification_rejects_invalid_signatures(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson("/api/email/verify/{$user->id}/".sha1($user->email))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_customer_can_resend_email_verification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->postJson('/api/email/verification-notification', [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Verification link sent.');

        Notification::assertSentTo($user, VerifyEmailApi::class);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('current')->plainTextToken;
        $user->createToken('other');

        $this->withToken($currentToken)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->app['auth']->forgetGuards();
        $this->withToken($currentToken)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }
}
