<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserRoleAndStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_active_customer(): void
    {
        $this->assertTrue(
            Schema::hasColumns('users', ['role', 'status']),
            'The users table must expose role and status defaults.'
        );

        $user = User::factory()->create()->fresh();

        $this->assertSame('customer', $user->getRawOriginal('role'));
        $this->assertSame('active', $user->getRawOriginal('status'));
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isActive());
    }

    public function test_role_and_status_are_cast_to_enums(): void
    {
        $this->assertTrue(enum_exists(UserRole::class));
        $this->assertTrue(enum_exists(UserStatus::class));
        $this->assertTrue(Schema::hasColumns('users', ['role', 'status']));

        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'disabled',
        ]);

        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertSame(UserStatus::Disabled, $user->status);
    }

    public function test_admin_and_disabled_factory_states_are_explicit(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['role', 'status']));

        $admin = User::factory()->admin()->create();
        $disabled = User::factory()->disabled()->create();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertSame(UserStatus::Disabled, $disabled->status);
        $this->assertFalse($disabled->isActive());
    }

    public function test_public_registration_ignores_injected_role_and_status(): void
    {
        $response = $this->post('/register', [
            'name' => 'Cliente Fictício',
            'email' => 'cliente@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
            'status' => 'disabled',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'cliente@example.test')->firstOrFail();

        $this->assertSame('customer', $user->getRawOriginal('role'));
        $this->assertSame('active', $user->getRawOriginal('status'));
    }
}
