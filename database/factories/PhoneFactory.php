<?php

namespace Database\Factories;

use App\Domain\PersonalData\BlindIndex;
use App\Enums\PhoneType;
use App\Models\Phone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Phone> */
class PhoneFactory extends Factory
{
    protected $model = Phone::class;

    public function definition(): array
    {
        $digits = '119'.str_pad((string) $this->faker->unique()->numberBetween(0, 99_999_999), 8, '0', STR_PAD_LEFT);
        $formatted = sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));

        return [
            'user_id' => User::factory(),
            'number_encrypted' => $formatted,
            'number_hash' => (new BlindIndex)->for($digits),
            'type' => PhoneType::Mobile,
            'is_primary' => true,
        ];
    }
}
