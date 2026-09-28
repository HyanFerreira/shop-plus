<?php

namespace App\Domain\PersonalData;

use InvalidArgumentException;

final readonly class BrazilianPhone
{
    private function __construct(private string $digits)
    {
    }

    public static function from(string $value): self
    {
        if (! preg_match('/^[0-9()\-\s]+$/', $value)) {
            throw new InvalidArgumentException('Telefone inválido.');
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        $length = strlen($digits);
        $validDdd = isset($digits[0], $digits[1]) && $digits[0] !== '0' && $digits[1] !== '0';
        $validSubscriber = ($length === 11 && $digits[2] === '9')
            || ($length === 10 && in_array($digits[2], ['2', '3', '4', '5'], true));

        if (! $validDdd || ! $validSubscriber) {
            throw new InvalidArgumentException('Telefone inválido.');
        }

        return new self($digits);
    }

    public static function isValid(string $value): bool
    {
        try {
            self::from($value);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function digits(): string
    {
        return $this->digits;
    }

    public function formatted(): string
    {
        $prefixLength = strlen($this->digits) === 11 ? 5 : 4;

        return sprintf(
            '(%s) %s-%s',
            substr($this->digits, 0, 2),
            substr($this->digits, 2, $prefixLength),
            substr($this->digits, 2 + $prefixLength),
        );
    }

    public function masked(): string
    {
        $mask = strlen($this->digits) === 11 ? '*****' : '****';

        return sprintf('(%s) %s-%s', substr($this->digits, 0, 2), $mask, substr($this->digits, -4));
    }
}
