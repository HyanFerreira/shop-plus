<?php

namespace Tests\Feature\PersonalData;

use App\Domain\PersonalData\BlindIndex;
use App\Domain\PersonalData\Cpf;
use App\Livewire\PersonalData\Manager;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_personal_data_page(): void
    {
        $this->get('/meus-dados')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_render_personal_data_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/meus-dados')
            ->assertOk()
            ->assertSeeText('Meus dados')
            ->assertSeeLivewire(Manager::class);
    }

    public function test_customer_can_create_and_update_their_profile(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->set('cpf', '123.456.789-09')
            ->set('birthDate', '1990-05-20')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertSet('cpf', '123.456.789-09');

        $profile = $user->customerProfile()->firstOrFail();

        $this->assertSame('123.456.789-09', $profile->cpf_encrypted);
        $this->assertSame((new BlindIndex)->for('12345678909'), $profile->cpf_hash);

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->set('cpf', '111.444.777-35')
            ->set('birthDate', '1991-06-21')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame($profile->id, $user->customerProfile()->firstOrFail()->id);
        $this->assertSame('111.444.777-35', $user->customerProfile()->firstOrFail()->cpf_encrypted);
    }

    public function test_existing_profile_is_loaded_in_canonical_format(): void
    {
        $user = User::factory()->create();
        $cpf = Cpf::from('12345678909');

        $user->customerProfile()->create([
            'cpf_encrypted' => $cpf->digits(),
            'cpf_hash' => (new BlindIndex)->for($cpf->digits()),
            'birth_date' => '1990-05-20',
        ]);

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->assertSet('cpf', '123.456.789-09')
            ->assertSet('birthDate', '1990-05-20');
    }

    public function test_invalid_cpf_is_rejected(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Manager::class)
            ->set('cpf', '000.000.000-00')
            ->call('saveProfile')
            ->assertHasErrors(['cpf']);

        $this->assertDatabaseCount('customer_profiles', 0);
    }

    public function test_duplicate_cpf_is_rejected_without_revealing_another_profile(): void
    {
        $cpf = Cpf::from('123.456.789-09');
        CustomerProfile::factory()->create([
            'cpf_encrypted' => $cpf->formatted(),
            'cpf_hash' => (new BlindIndex)->for($cpf->digits()),
        ]);
        $secondUser = User::factory()->create();

        Livewire::actingAs($secondUser)
            ->test(Manager::class)
            ->set('cpf', $cpf->formatted())
            ->call('saveProfile')
            ->assertHasErrors(['cpf']);

        $this->assertNull($secondUser->customerProfile);
    }

    public function test_profile_save_cannot_target_another_user(): void
    {
        $victim = User::factory()->create();
        $victimProfile = CustomerProfile::factory()->for($victim)->create();
        $attacker = User::factory()->create();

        Livewire::actingAs($attacker)
            ->test(Manager::class)
            ->set('cpf', '123.456.789-09')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame($victimProfile->cpf_hash, $victimProfile->fresh()->cpf_hash);
        $this->assertSame($attacker->id, $attacker->customerProfile()->firstOrFail()->user_id);
    }
}
