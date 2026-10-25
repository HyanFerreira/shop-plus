<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class AuthFlowProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_login_feedback_is_associated_and_password_is_not_reflected(): void
    {
        $this->from('/login')->post('/login', ['email' => 'unknown@example.test', 'password' => 'NeverReflectThis123!'])->assertSessionHasErrors('email');
        $this->get('/login')->assertOk()->assertSee('email-error')->assertSee('aria-invalid="true"', false)->assertDontSee('NeverReflectThis123!');
        $this->assertGuest();
    }

    public function test_registration_boundaries_and_duplicate_email_are_preserved(): void
    {
        User::factory()->create(['email' => 'duplicate@example.test']);
        $base = ['name' => 'Cliente', 'email' => 'new@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'];
        foreach ([
            [[], ['name', 'email', 'password']],
            [array_replace($base, ['name' => str_repeat('N', 256)]), ['name']],
            [array_replace($base, ['email' => 'invalid-email']), ['email']],
            [array_replace($base, ['email' => str_repeat('a', 256).'@example.test']), ['email']],
            [array_replace($base, ['email' => 'duplicate@example.test']), ['email']],
            [array_replace($base, ['password' => 'short', 'password_confirmation' => 'short']), ['password']],
            [array_replace($base, ['password' => str_repeat('P', 129), 'password_confirmation' => str_repeat('P', 129)]), ['password', 'password_confirmation']],
            [array_replace($base, ['password_confirmation' => 'Mismatch123!']), ['password']],
        ] as [$input,$errors]) {
            $this->postJson('/register', $input)->assertUnprocessable()->assertJsonValidationErrors($errors);
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_recovery_is_neutral_and_full_reset_retains_token_security(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.test']);
        $known = $this->postJson('/forgot-password', ['email' => $user->email])->assertOk()->json('message');
        $unknown = $this->postJson('/forgot-password', ['email' => 'unknown@example.test'])->assertOk()->json('message');
        $this->assertSame($known, $unknown);
        $notification = Notification::sent($user, ResetPassword::class)->first();
        $this->assertNotNull($notification);
        $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email))->assertOk()->assertSee('name="token"', false);
        $payload = ['token' => $notification->token, 'email' => $user->email, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'];
        $this->postJson('/reset-password', array_replace($payload, ['token' => 'invalid-token']))->assertUnprocessable();
        $this->post('/reset-password', $payload)->assertSessionHasNoErrors()->assertRedirect('/login');
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->postJson('/reset-password', $payload)->assertUnprocessable();
        $this->post('/login', ['email' => $user->email, 'password' => 'NewPassword123!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_two_factor_still_requires_valid_code_and_accepts_recovery_once(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-test'])), 'two_factor_confirmed_at' => now()])->save();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
        $this->get('/two-factor-challenge')->assertOk()->assertSee('recovery_code');
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->withSession(['login.id' => $user->id, 'login.remember' => false])->post('/two-factor-challenge', ['recovery_code' => 'recovery-code-test'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotContains('recovery-code-test', $user->fresh()->recoveryCodes());
    }

    public function test_existing_auth_rate_limits_remain_effective(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', ['email' => 'rate-login@example.test', 'password' => 'wrong-password'])->assertUnprocessable();
        }
        $this->postJson('/login', ['email' => 'rate-login@example.test', 'password' => 'wrong-password'])->assertStatus(429);
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/register', ['name' => '', 'email' => 'rate-register@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'])->assertUnprocessable();
        }
        $this->postJson('/register', ['name' => '', 'email' => 'rate-register@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'])->assertStatus(429);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/forgot-password', ['email' => 'rate-reset@example.test'])->assertOk();
        }
        $this->postJson('/forgot-password', ['email' => 'rate-reset@example.test'])->assertStatus(429);
        $this->assertGuest();
        $this->assertDatabaseCount('users',0);
    }
}
