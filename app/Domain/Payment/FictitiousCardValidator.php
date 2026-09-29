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
        $pan = preg_replace('/\D/', '', $number) ?? '';
        $knownCards = [self::APPROVED_VISA, self::APPROVED_MASTERCARD, self::DECLINED_VISA];

        if (! in_array($pan, $knownCards, true) || ! $this->passesLuhn($pan) || ! preg_match('/^\d{3,4}$/', $cvv)) {
            throw ValidationException::withMessages(['cardNumber' => 'Dados do cartão fictício inválidos.']);
        }

        $expiresAt = now()->setDate($expiryYear, $expiryMonth, 1)->endOfMonth();
        if ($expiryMonth < 1 || $expiryMonth > 12 || $expiryYear > now()->year + 15 || $expiresAt->isPast()) {
            throw ValidationException::withMessages(['cardExpiry' => 'Validade do cartão fictício inválida.']);
        }

        return [
            'authorized' => $pan !== self::DECLINED_VISA,
            'brand' => str_starts_with($pan, '5') ? 'mastercard' : 'visa',
            'last_four' => substr($pan, -4),
        ];
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
