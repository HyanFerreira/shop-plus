<?php

namespace App\Domain\Checkout;

use Illuminate\Validation\ValidationException;

class ShippingCalculator
{
    /** @return array<string, array{label: string, price_cents: int, days: int}> */
    public function options(string $postalCode, int $weightGrams): array
    {
        $digits = preg_replace('/\D/', '', $postalCode) ?? '';

        if (strlen($digits) !== 8 || $weightGrams < 0) {
            throw ValidationException::withMessages(['shipping' => 'Não foi possível calcular o frete.']);
        }

        $kilograms = max(1, (int) ceil($weightGrams / 1000));
        $remoteSurcharge = (int) $digits[0] >= 7 ? 1000 : 0;

        return [
            'economy' => ['label' => 'Econômica', 'price_cents' => 1290 + ($kilograms * 200) + $remoteSurcharge, 'days' => $remoteSurcharge ? 10 : 7],
            'express' => ['label' => 'Expressa', 'price_cents' => 2490 + ($kilograms * 400) + $remoteSurcharge, 'days' => $remoteSurcharge ? 5 : 3],
            'pickup' => ['label' => 'Retirada', 'price_cents' => 0, 'days' => 1],
        ];
    }
}
