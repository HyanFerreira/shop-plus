<?php

namespace App\Domain\PersonalData;

use InvalidArgumentException;

final readonly class Cpf
{
    private function __construct(private string $digits)
    {
    }

    public static function from(string $value): self
    {
        if (! self::isValid($value)) {
            throw new InvalidArgumentException('CPF inválido.');
        }

        return new self(self::normalize($value));
    }

    public static function isValid(string $value): bool
    {
        if (! preg_match('/^[0-9.\-\s]+$/', $value)) {
            return false;
        }

        $digits = self::normalize($value);

        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        return self::checkDigit(substr($digits, 0, 9), 10) === (int) $digits[9]
            && self::checkDigit(substr($digits, 0, 10), 11) === (int) $digits[10];
    }

    public function digits(): string
    {
        return $this->digits;
    }

    public function formatted(): string
    {
        return sprintf(
            '%s.%s.%s-%s',
            substr($this->digits, 0, 3),
            substr($this->digits, 3, 3),
            substr($this->digits, 6, 3),
            substr($this->digits, 9, 2),
        );
    }

    public function masked(): string
    {
        return sprintf('***.%s.***-%s', substr($this->digits, 3, 3), substr($this->digits, 9, 2));
    }

    private static function normalize(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }

    private static function checkDigit(string $digits, int $initialWeight): int
    {
        $sum = 0;

        foreach (str_split($digits) as $index => $digit) {
            $sum += (int) $digit * ($initialWeight - $index);
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
