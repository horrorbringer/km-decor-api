<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_super_admin_can_access_admin_panel(): void
    {
        $this->seed(AdminUserSeeder::class);

        $user = User::where('email', 'admin@kmdecor.com')->firstOrFail();
        $panel = Filament::getPanel('admin');

        $this->assertSame('super_admin', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_customer_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $panel = Filament::getPanel('admin');

        $this->assertFalse($user->canAccessPanel($panel));
    }
}
