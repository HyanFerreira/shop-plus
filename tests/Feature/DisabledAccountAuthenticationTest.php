<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisabledAccountAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_authenticate_with_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_disabled_user_cannot_authenticate_with_correct_password(): void
    {
        $user = User::factory()->disabled()->create([
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest('web');
        $response->assertSessionHasErrors('email');
    }

    public function test_disabled_account_uses_the_generic_failed_authentication_message(): void
    {
        $disabled = User::factory()->disabled()->create([
            'email' => 'disabled@example.test',
            'password' => 'password',
        ]);
        $active = User::factory()->create([
            'email' => 'active@example.test',
            'password' => 'password',
        ]);

        $disabledResponse = $this->post('/login', [
            'email' => $disabled->email,
            'password' => 'password',
        ]);
        $disabledErrors = $disabledResponse->getSession()->get('errors')?->get('email') ?? [];

        $invalidPasswordResponse = $this->post('/login', [
            'email' => $active->email,
            'password' => 'incorrect-password',
        ]);
        $invalidPasswordErrors = $invalidPasswordResponse->getSession()->get('errors')?->get('email') ?? [];

        $this->assertGuest('web');
        $this->assertSame($invalidPasswordErrors, $disabledErrors);
        $this->assertSame([__('auth.failed')], $disabledErrors);
    }

    public function test_disabled_authenticated_session_is_terminated_on_next_web_request(): void
    {
        $user = User::factory()->disabled()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login', absolute: false));
        $this->assertGuest('web');
    }
}
