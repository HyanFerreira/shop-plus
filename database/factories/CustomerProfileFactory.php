<?php

namespace Database\Factories;

use App\Domain\PersonalData\BlindIndex;
use App\Domain\PersonalData\Cpf;
use App\Models\CustomerProfile;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomerProfile> */
class CustomerProfileFactory extends Factory
{
    protected $model = CustomerProfile::class;

    public function definition(): array
    {
        static $faker;

        $faker ??= FakerFactory::create('pt_BR');
        $cpf = Cpf::from($faker->unique()->cpf());

        return [
            'user_id' => User::factory(),
            'cpf_encrypted' => $cpf->formatted(),
            'cpf_hash' => (new BlindIndex)->for($cpf->digits()),
            'birth_date' => $this->faker->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
        ];
    }
}
