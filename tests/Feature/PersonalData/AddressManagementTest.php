<?php

namespace Tests\Feature\PersonalData;

use App\Livewire\PersonalData\Manager;
use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AddressManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_a_complete_encrypted_address(): void
    {
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(Manager::class);

        $this->fillAddress($component)->call('saveAddress')->assertHasNoErrors();

        $address = $user->addresses()->firstOrFail();
        $raw = DB::table('addresses')->find($address->id);

        $this->assertTrue($address->is_primary);
        $this->assertSame('01001-000', $address->postal_code_encrypted);
        $this->assertSame('SP', $address->state_encrypted);

        foreach (Address::ENCRYPTED_FIELDS as $field) {
            if ($address->{$field} !== null) {
                $this->assertNotSame($address->{$field}, $raw->{$field});
            }
        }
    }

    public function test_invalid_postal_code_and_state_are_rejected(): void
    {
        $component = Livewire::actingAs(User::factory()->create())->test(Manager::class);

        $this->fillAddress($component, ['addressPostalCode' => '123', 'addressState' => 'XX'])
            ->call('saveAddress')
            ->assertHasErrors(['addressPostalCode', 'addressState']);

        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_customer_can_edit_an_address_and_switch_the_primary_address(): void
    {
        $user = User::factory()->create();
        $first = Address::factory()->for($user)->create(['is_primary' => true]);
        $second = Address::factory()->for($user)->create(['is_primary' => false]);
        $component = Livewire::actingAs($user)->test(Manager::class)->call('editAddress', $second->id);

        $this->fillAddress($component, [
            'addressLabel' => 'Trabalho fictício',
            'addressCity' => 'Outra Cidade Fictícia',
            'addressIsPrimary' => true,
        ])->call('saveAddress')->assertHasNoErrors();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame('Trabalho fictício', $second->fresh()->label);
        $this->assertSame('Outra Cidade Fictícia', $second->fresh()->city_encrypted);
    }

    public function test_address_edit_and_delete_are_scoped_to_the_authenticated_owner(): void
    {
        $owner = User::factory()->create();
        $address = Address::factory()->for($owner)->create();
        $attacker = User::factory()->create();

        Livewire::actingAs($attacker)
            ->test(Manager::class)
            ->call('editAddress', $address->id)
            ->assertHasErrors(['addressLabel'])
            ->call('deleteAddress', $address->id)
            ->assertHasErrors(['addressLabel']);

        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'user_id' => $owner->id]);
    }

    public function test_deleting_primary_address_promotes_the_oldest_remaining_address(): void
    {
        $user = User::factory()->create();
        $primary = Address::factory()->for($user)->create(['is_primary' => true]);
        $replacement = Address::factory()->for($user)->create(['is_primary' => false]);

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->call('deleteAddress', $primary->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('addresses', ['id' => $primary->id]);
        $this->assertTrue($replacement->fresh()->is_primary);
    }

    public function test_persisted_labels_are_escaped_and_address_details_are_masked(): void
    {
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(Manager::class);
        $unsafeLabel = '<script>alert("x")</script>';

        $this->fillAddress($component, ['addressLabel' => $unsafeLabel])
            ->call('saveAddress')
            ->assertHasNoErrors()
            ->assertSee($unsafeLabel)
            ->assertDontSee($unsafeLabel, false)
            ->assertSee('*****-***')
            ->assertDontSee('01001-000');
    }

    /** @param array<string, string|bool> $overrides */
    private function fillAddress(Testable $component, array $overrides = []): Testable
    {
        $values = array_merge([
            'addressLabel' => 'Casa fictícia',
            'addressRecipient' => 'Cliente de Teste',
            'addressPostalCode' => '01001000',
            'addressStreet' => 'Rua de Teste Automatizado',
            'addressNumber' => '123',
            'addressComplement' => 'Unidade 4',
            'addressDistrict' => 'Bairro de Teste',
            'addressCity' => 'Cidade Fictícia',
            'addressState' => 'SP',
            'addressIsPrimary' => false,
        ], $overrides);

        foreach ($values as $property => $value) {
            $component->set($property, $value);
        }

        return $component;
    }
}
