<?php

namespace Tests\Feature\Security;

use App\Actions\Fortify\CreateNewUser;
use App\Livewire\PersonalData\Manager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdversarialInputTest extends TestCase
{
    use RefreshDatabase;

    public static function malformedAuth(): array
    {
        return [
            'login email array' => ['/login', ['email' => ['bad'], 'password' => 'password']],
            'login password array' => ['/login', ['email' => 'fake@example.test', 'password' => ['bad']]],
            'registration email array' => ['/register', ['email' => ['bad']]],
            'reset email array' => ['/forgot-password', ['email' => ['bad']]],
            'login oversized email' => ['/login', ['email' => str_repeat('a', 1001), 'password' => 'password']],
            'login oversized password' => ['/login', ['email' => 'fake@example.test', 'password' => str_repeat('a', 1001)]],
        ];
    }

    #[DataProvider('malformedAuth')]
    public function test_auth_rejects_malformed_input_without_internal_errors(string $url, array $input): void
    {
        $this->postJson($url, $input)->assertUnprocessable();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_oversized_password_before_hashing(): void
    {
        $password = str_repeat('a', 1001);
        try {
            app(CreateNewUser::class)->create(['name' => 'Cliente', 'email' => 'fake@example.test', 'password' => $password, 'password_confirmation' => $password]);
            $this->fail('Oversized password accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('password', $exception->errors());
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_personal_identifiers_cannot_bypass_limits_with_padding(): void
    {
        Livewire::actingAs(User::factory()->create())->test(Manager::class)
            ->set('cpf', str_repeat(' ', 1001).'529.982.247-25')->call('saveProfile')->assertHasErrors('cpf')
            ->set('phoneNumber', str_repeat(' ', 1001).'(11) 98765-4321')->call('savePhone')->assertHasErrors('phoneNumber');
        $this->assertDatabaseCount('customer_profiles', 0);
        $this->assertDatabaseCount('phones', 0);
    }

    public function test_catalog_rejects_nested_filter_arrays(): void
    {
        $this->get('/catalogo?selectedCategories[0][bad]=value')->assertUnprocessable();
    }

    public function test_catalog_rejects_unbounded_search(): void
    {
        $this->get('/catalogo?q='.str_repeat('a', 1001))->assertUnprocessable();
    }

    public function test_catalog_url_rejects_arrays_before_typed_properties_are_set(): void
    {
        foreach (['q', 'category', 'sort', 'perPage', 'minimumRating', 'minPrice', 'maxPrice', 'inStock'] as $field) {
            $this->get('/catalogo?'.$field.'[]=invalid')->assertUnprocessable();
        }
    }
}
