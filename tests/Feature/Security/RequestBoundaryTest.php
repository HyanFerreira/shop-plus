<?php

namespace Tests\Feature\Security;

use App\Livewire\Catalog\Browser;
use App\Livewire\PaymentForm;
use App\Livewire\PersonalData\Manager;
use App\Livewire\ProductAddToCart;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RequestBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public static function csrfRoutes(): array
    {
        return array_map(fn ($url) => [$url], ['/register', '/login', '/forgot-password', '/reset-password', '/logout', '/livewire/update']);
    }

    #[DataProvider('csrfRoutes')]
    public function test_csrf_middleware_rejects_missing_and_incorrect_tokens(string $url): void
    {
        // Turn off only Laravel's test-only CSRF bypass. DB configuration and guards stay intact.
        $this->app->instance('env', 'production');
        try {
            $this->postJson($url)->assertStatus(419);
            $this->withSession(['_token' => 'session-csrf-token'])->postJson($url, ['_token' => 'incorrect-token'])->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_correct_csrf_token_reaches_registration_validation(): void
    {
        $this->app->instance('env', 'production');
        try {
            $this->withSession(['_token' => 'session-csrf-token'])
                ->postJson('/register', ['_token' => 'session-csrf-token'])
                ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_livewire_rejects_array_in_scalar_field_before_hydration(): void
    {
        $component = Livewire::actingAs(User::factory()->create())->test(Manager::class);
        $this->postJson(Livewire::getUpdateUri(), [
            'components' => [[
                'snapshot' => json_encode($component->snapshot),
                'updates' => ['addressLabel' => ['invalid']],
                'calls' => [],
            ]],
        ], ['X-Livewire' => 'true'])->assertUnprocessable();
        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_livewire_rejects_malformed_action_parameters(): void
    {
        $component = Livewire::actingAs(User::factory()->create())->test(Manager::class);
        $this->postJson(Livewire::getUpdateUri(), [
            'components' => [[
                'snapshot' => json_encode($component->snapshot),
                'updates' => (object) [],
                'calls' => [['method' => 'deleteAddress', 'params' => [['invalid']], 'path' => '']],
            ]],
        ], ['X-Livewire' => 'true'])->assertUnprocessable();
    }

    public function test_pagination_rejects_array_page_name_and_nested_page_state(): void
    {
        $component = Livewire::test(Browser::class);
        foreach ([
            ['updates' => (object) [], 'calls' => [['method' => 'gotoPage', 'params' => [1, ['bad']], 'path' => '']]],
            ['updates' => ['paginators.page' => ['bad']], 'calls' => []],
            ['updates' => ['paginators' => ['page' => ['bad']]], 'calls' => []],
            ['updates' => (object) [], 'calls' => [['method' => 'gotoPage', 'params' => [1000000000], 'path' => '']]],
        ] as $request) {
            $this->postJson(Livewire::getUpdateUri(), ['components' => [
                ['snapshot' => json_encode($component->snapshot)] + $request,
            ]], ['X-Livewire' => 'true'])->assertUnprocessable();
        }
    }

    public function test_empty_numeric_form_field_keeps_normal_validation_errors(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();
        Livewire::actingAs($user)->test(PaymentForm::class, ['order' => $order])
            ->set('expiryMonth', '')->call('pay')->assertHasErrors('expiryMonth');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_empty_quantity_can_be_corrected_using_existing_increment_button(): void
    {
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        Livewire::actingAs(User::factory()->create())->test(ProductAddToCart::class, ['product' => $product])
            ->set('quantity', '')->call('increment')->assertSet('quantity', 2);
    }
}
