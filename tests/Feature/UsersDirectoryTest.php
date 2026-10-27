<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UsersDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return ['customer' => [false], 'administrator' => [true]];
    }

    #[DataProvider('roles')]
    public function test_active_accounts_of_every_role_can_read_the_directory(bool $admin): void
    {
        $factory = User::factory();
        $user = ($admin ? $factory->admin() : $factory)->create();
        $response = $this->actingAs($user)->get('/usuarios')->assertOk()
            ->assertSee('Usuários cadastrados')->assertSee('Atualizar');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertFalse($user->fresh()->is_automated_demo);
    }

    public function test_guests_and_disabled_accounts_cannot_read_the_directory(): void
    {
        $this->get('/usuarios')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->disabled()->create())->get('/usuarios')->assertRedirect(route('login'));
        $this->assertGuest('web');
    }

    public function test_unverified_active_accounts_can_read_the_directory(): void
    {
        $this->actingAs(User::factory()->unverified()->create())->get('/usuarios')->assertOk();
    }

    public function test_counts_include_real_accounts_but_html_and_selected_columns_exclude_their_private_data(): void
    {
        $this->freezeTime();
        $viewer = User::factory()->create(['created_at' => now()->subHour()]);
        User::factory()->create(['name' => 'Terceira Pessoa Privada', 'email' => 'privada@example.test', 'created_at' => now()->subMinute()]);
        $generated = User::factory()->create(['name' => 'Pessoa Fictícia 1', 'email' => 'automatico-secreto@example.test', 'is_automated_demo' => true, 'created_at' => now()->subMinutes(2)]);
        User::factory()->disabled()->create(['created_at' => now()->subMinutes(6)]);
        $response = $this->actingAs($viewer)->get('/usuarios')->assertOk()
            ->assertViewHas('totalUsers', 4)->assertViewHas('automatedUsersCount', 1)->assertViewHas('recentUsersCount', 2)
            ->assertSee('Pessoa Fictícia 1')->assertSee('Automático')
            ->assertDontSee('Terceira Pessoa Privada')->assertDontSee('privada@example.test')
            ->assertDontSee('automatico-secreto@example.test');
        $this->assertSame(['id', 'name', 'created_at'], array_keys($response->viewData('automatedUsers')->first()->getAttributes()));
        $this->assertSame($generated->id, $response->viewData('automatedUsers')->first()->id);
    }

    public function test_directory_paginates_twenty_rows_in_stable_newest_order_and_uses_sao_paulo_dates(): void
    {
        $viewer = User::factory()->create();
        $users = User::factory()->count(21)->create(['is_automated_demo' => true, 'created_at' => '2026-10-26 02:30:00']);
        $response = $this->actingAs($viewer)->get('/usuarios')->assertOk()
            ->assertSee('25/10/2026 23:30:00')->assertSee('2026-10-25T23:30:00-03:00', false);
        $page = $response->viewData('automatedUsers');
        $this->assertSame(20, $page->count());
        $this->assertSame(21, $page->total());
        $this->assertSame($users->reverse()->take(20)->pluck('id')->values()->all(), $page->pluck('id')->all());
        $this->get('/usuarios?page=2')->assertOk()->assertViewHas('automatedUsers', fn ($usersPage) => $usersPage->count() === 1 && $usersPage->first()->id === $users->first()->id);
    }

    public function test_recent_count_uses_five_minute_window_and_refresh_observes_new_accounts(): void
    {
        $this->freezeTime();
        $viewer = User::factory()->create(['created_at' => now()->subHour()]);
        User::factory()->create(['created_at' => now()->subMinutes(5)]);
        User::factory()->create(['created_at' => now()->subMinutes(5)->subSecond()]);
        $this->actingAs($viewer)->get('/usuarios')->assertOk()->assertViewHas('recentUsersCount', 1)->assertViewHas('totalUsers', 3);
        User::factory()->create(['is_automated_demo' => true]);
        $this->get('/usuarios')->assertOk()->assertViewHas('recentUsersCount', 2)->assertViewHas('totalUsers', 4)->assertViewHas('automatedUsersCount', 1);
    }

    public function test_directory_is_read_only_and_does_not_grant_admin_access(): void
    {
        $this->actingAs(User::factory()->create())->get('/usuarios')->assertOk()->assertSee('Nenhuma conta fictícia gerada ainda.');
        $this->post('/usuarios')->assertStatus(405);
        $this->put('/usuarios')->assertStatus(405);
        $this->delete('/usuarios')->assertStatus(405);
        $this->get(route('admin.users'))->assertForbidden();
    }
}
