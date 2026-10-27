<?php

namespace App\Domain\Payment;

use Illuminate\Validation\ValidationException;

class FictitiousCardValidator
{
    public const APPROVED_VISA = '4111111111111111';

    public const APPROVED_MASTERCARD = '5555555555554444';

    public const DECLINED_VISA = '4000000000000002';

    /** @return array{authorized: bool, brand: string, last_four: string} */
    public function validate(string $number, string $cvv, int $expiryMonth, int $expiryYear): array
    {
        // Only ASCII spaces separate digits; never silently discard arbitrary input.
        $pan = str_replace(' ', '', $number);
        $brand = $this->brand($pan);
        if (strlen($number) > 37 || ! preg_match('/\A[0-9 ]+\z/', $number)
            || strlen($pan) < 13 || strlen($pan) > 19
            || preg_match('/\A([0-9])\1+\z/', $pan)
            || $brand === null || ! $this->passesLuhn($pan)
            || ! preg_match('/\A[0-9]{3}\z/', $cvv)) {
            throw ValidationException::withMessages(['cardNumber' => 'Dados do cartão fictício inválidos.']);
        }

        $today = now();
        // Validate the range before Carbon can normalize malformed month/year values.
        if ($expiryMonth < 1 || $expiryMonth > 12
            || $expiryYear < $today->year || $expiryYear > $today->year + 15
            || ($expiryYear === $today->year && $expiryMonth < $today->month)) {
            throw ValidationException::withMessages(['cardExpiry' => 'Validade do cartão fictício inválida.']);
        }

        return [
            'authorized' => $pan !== self::DECLINED_VISA,
            'brand' => $brand,
            'last_four' => substr($pan, -4),
        ];
    }

    private function brand(string $pan): ?string
    {
        $length = strlen($pan);
        if (str_starts_with($pan, '4') && in_array($length, [13, 16, 19], true)) {
            return 'visa';
        }

        $prefix = (int) substr($pan, 0, 4);
        if ($length === 16 && (preg_match('/\A5[1-5]/', $pan) || ($prefix >= 2221 && $prefix <= 2720))) {
            return 'mastercard';
        }

        return null;
    }

    private function passesLuhn(string $number): bool
    {
        $sum = 0;
        $parity = strlen($number) % 2;
        foreach (str_split($number) as $index => $character) {
            $digit = (int) $character;
            if ($index % 2 === $parity) {
                $digit *= 2;
                $digit = $digit > 9 ? $digit - 9 : $digit;
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
