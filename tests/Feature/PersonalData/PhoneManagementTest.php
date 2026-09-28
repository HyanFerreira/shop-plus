<?php

namespace Tests\Feature\PersonalData;

use App\Domain\PersonalData\BlindIndex;
use App\Livewire\PersonalData\Manager;
use App\Models\Phone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PhoneManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_a_normalized_encrypted_phone(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->set('phoneNumber', '(11) 99999-0000')
            ->set('phoneType', 'mobile')
            ->call('savePhone')
            ->assertHasNoErrors();

        $phone = $user->phones()->firstOrFail();
        $raw = DB::table('phones')->find($phone->id);

        $this->assertSame('(11) 99999-0000', $phone->number_encrypted);
        $this->assertSame((new BlindIndex)->for('11999990000'), $phone->number_hash);
        $this->assertNotSame($phone->number_encrypted, $raw->number_encrypted);
        $this->assertTrue($phone->is_primary);
    }

    public function test_invalid_and_duplicate_normalized_phones_are_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->set('phoneNumber', '123')
            ->call('savePhone')
            ->assertHasErrors(['phoneNumber']);

        Phone::factory()->for($user)->create([
            'number_encrypted' => '(11) 99999-0000',
            'number_hash' => (new BlindIndex)->for('11999990000'),
        ]);

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->set('phoneNumber', '11 99999 0000')
            ->set('phoneType', 'mobile')
            ->call('savePhone')
            ->assertHasErrors(['phoneNumber']);

        $this->assertSame(1, $user->phones()->count());
    }

    public function test_customer_can_edit_a_phone_and_switch_the_primary_phone(): void
    {
        $user = User::factory()->create();
        $first = Phone::factory()->for($user)->create(['is_primary' => true]);
        $second = Phone::factory()->for($user)->create(['is_primary' => false]);

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->call('editPhone', $second->id)
            ->assertSet('phoneId', $second->id)
            ->set('phoneNumber', '(21) 98888-7777')
            ->set('phoneType', 'work')
            ->set('phoneIsPrimary', true)
            ->call('savePhone')
            ->assertHasNoErrors();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame('(21) 98888-7777', $second->fresh()->number_encrypted);
        $this->assertSame('work', $second->fresh()->type->value);
    }

    public function test_phone_edit_and_delete_are_scoped_to_the_authenticated_owner(): void
    {
        $owner = User::factory()->create();
        $phone = Phone::factory()->for($owner)->create();
        $attacker = User::factory()->create();

        Livewire::actingAs($attacker)
            ->test(Manager::class)
            ->call('editPhone', $phone->id)
            ->assertHasErrors(['phoneNumber'])
            ->call('deletePhone', $phone->id)
            ->assertHasErrors(['phoneNumber']);

        $this->assertDatabaseHas('phones', ['id' => $phone->id, 'user_id' => $owner->id]);
    }

    public function test_deleting_primary_phone_promotes_the_oldest_remaining_phone(): void
    {
        $user = User::factory()->create();
        $primary = Phone::factory()->for($user)->create(['is_primary' => true]);
        $replacement = Phone::factory()->for($user)->create(['is_primary' => false]);

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->call('deletePhone', $primary->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('phones', ['id' => $primary->id]);
        $this->assertTrue($replacement->fresh()->is_primary);
    }

    public function test_phone_list_displays_only_a_masked_number(): void
    {
        $user = User::factory()->create();
        Phone::factory()->for($user)->create([
            'number_encrypted' => '(11) 99999-0000',
            'number_hash' => (new BlindIndex)->for('11999990000'),
        ]);

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->assertSee('(11) *****-0000')
            ->assertDontSee('(11) 99999-0000');
    }
}
