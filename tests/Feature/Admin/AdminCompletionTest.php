<?php

namespace Tests\Feature\Admin;

use App\Actions\Admin\CreateAdministrator;
use App\Actions\Admin\SetUserStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Admin\AuditManager;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_disable_another_admin_but_not_self(): void
    {
        $actor = User::factory()->admin()->create();
        $created = app(CreateAdministrator::class)->execute($actor, ['name' => 'Admin Fictício', 'email' => 'novo-admin@example.test', 'password' => 'Senha-Forte-123!', 'password_confirmation' => 'Senha-Forte-123!']);

        $this->assertSame(UserRole::Admin, $created->role);
        app(SetUserStatus::class)->execute($actor, $created, UserStatus::Disabled);
        $this->assertSame(UserStatus::Disabled, $created->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.created']);

        $this->expectException(ValidationException::class);
        app(SetUserStatus::class)->execute($actor, $actor, UserStatus::Disabled);
    }

    public function test_user_management_requires_recent_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.users'))->assertRedirect(route('password.confirm'));
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->get(route('admin.users'))->assertOk();
    }

    public function test_audit_screen_is_admin_only_masked_and_does_not_render_metadata(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'administrador@example.test']);
        AuditLog::create(['actor_id' => $admin->id, 'event' => 'security.test', 'metadata_encrypted' => ['secret' => 'never-render-this']]);

        Livewire::actingAs($admin)->test(AuditManager::class)
            ->assertSee('ad***@example.test')
            ->assertSee('security.test')
            ->assertDontSee('never-render-this');
        $this->actingAs(User::factory()->create())->get(route('admin.audit'))->assertForbidden();
    }

    public function test_sensitive_auth_endpoints_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.email'), ['email' => 'nobody@example.test'])->assertSessionHasNoErrors();
        }
        $this->post(route('password.email'), ['email' => 'nobody@example.test'])->assertTooManyRequests();
    }
}
