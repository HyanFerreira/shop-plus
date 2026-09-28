<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Http\Livewire\UpdatePasswordForm;
use Livewire\Livewire;
use Tests\TestCase;

class ArgonPasswordHashingTest extends TestCase
{
    use RefreshDatabase;

    public function test_newly_registered_password_uses_argon2id(): void
    {
        $this->post('/register', [
            'name' => 'Cliente Fictício',
            'email' => 'cliente@example.test',
            'password' => 'Correct-Horse-42!',
            'password_confirmation' => 'Correct-Horse-42!',
        ])->assertRedirect(route('dashboard', absolute: false));

        $hash = User::where('email', 'cliente@example.test')->firstOrFail()->password;

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue(Hash::check('Correct-Horse-42!', $hash));
    }

    public function test_updated_password_uses_argon2id(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(UpdatePasswordForm::class)
            ->set('state', [
                'current_password' => 'password',
                'password' => 'Updated-Password-42!',
                'password_confirmation' => 'Updated-Password-42!',
            ])
            ->call('updatePassword')
            ->assertHasNoErrors();

        $hash = $user->fresh()->password;

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue(Hash::check('Updated-Password-42!', $hash));
    }
}
