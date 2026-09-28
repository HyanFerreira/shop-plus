<?php

namespace App\Domain\Catalog;

use InvalidArgumentException;

final readonly class Price
{
    private function __construct(private int $cents)
    {
    }

    public static function fromInput(string $value): self
    {
        $normalized = str_replace(',', '.', trim($value));

        if (! preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/', $normalized, $matches)) {
            throw new InvalidArgumentException('Preço inválido.');
        }

        $fraction = str_pad($matches[2] ?? '', 2, '0');

        return new self(((int) $matches[1] * 100) + (int) $fraction);
    }

    public static function fromCents(int $cents): self
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Preço inválido.');
        }

        return new self($cents);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function input(): string
    {
        return intdiv($this->cents, 100).','.str_pad((string) ($this->cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public function brl(): string
    {
        $whole = number_format(intdiv($this->cents, 100), 0, ',', '.');
        $fraction = str_pad((string) ($this->cents % 100), 2, '0', STR_PAD_LEFT);

        return 'R$ '.$whole.','.$fraction;
    }
}
