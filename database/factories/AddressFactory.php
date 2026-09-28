<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Address> */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'Casa fictícia',
            'recipient_encrypted' => $this->faker->name().' (pessoa fictícia)',
            'postal_code_encrypted' => '01001-000',
            'street_encrypted' => 'Rua de Teste Automatizado',
            'number_encrypted' => (string) $this->faker->numberBetween(1, 9999),
            'complement_encrypted' => 'Unidade fictícia '.$this->faker->numberBetween(1, 99),
            'district_encrypted' => 'Bairro de Teste',
            'city_encrypted' => 'Cidade Fictícia',
            'state_encrypted' => 'SP',
            'is_primary' => true,
        ];
    }
}
