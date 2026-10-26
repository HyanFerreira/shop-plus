<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Jetstream;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationPasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    public static function invalidPasswords(): array
    {
        return [
            'empty' => ['', ''],
            'under eight characters' => ['Abcdef!', 'Abcdef!'],
            'no lowercase' => ['ABCDEFG!', 'ABCDEFG!'],
            'no uppercase' => ['abcdefg!', 'abcdefg!'],
            'no letters' => ['1234567!', '1234567!'],
            'no special character' => ['Abcdefgh', 'Abcdefgh'],
            'over maximum' => ['Ab!'.str_repeat('a', 126), 'Ab!'.str_repeat('a', 126)],
            'missing confirmation' => ['Abcdefg!', ''],
            'different confirmation' => ['Abcdefg!', 'Abcdefh!'],
        ];
    }

    #[DataProvider('invalidPasswords')]
    public function test_direct_registration_rejects_unmet_password_policy(string $password, string $confirmation): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Synthetic User', 'email' => 'policy@example.test',
            'password' => $password, 'password_confirmation' => $confirmation,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ])->assertSessionHasErrors($confirmation === '' && $password !== '' ? 'password_confirmation' : 'password');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public static function validPasswords(): array
    {
        return [
            'minimum without digits' => ['Abcdefg!'],
            'unicode letters and symbol' => ['Ábcdefg★'],
            'maximum' => ['Ab!'.str_repeat('a', 125)],
        ];
    }

    #[DataProvider('validPasswords')]
    public function test_valid_registration_persists_hash_and_authenticates_without_requiring_digits(string $password): void
    {
        $this->post('/register', [
            'name' => 'Synthetic User', 'email' => 'policy@example.test',
            'password' => $password, 'password_confirmation' => $password,
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
        $user = User::where('email', 'policy@example.test')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($password, $user->password);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_requirements_are_visible_without_javascript_and_only_on_registration(): void
    {
        $this->get('/register')->assertOk()->assertSee('Sua senha deve conter:')->assertSee('data-password-requirement="lowercase"', false);
        $this->get('/login')->assertOk()->assertDontSee('Sua senha deve conter:');
    }
}
