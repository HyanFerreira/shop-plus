<?php

namespace Tests\Unit\Domain\PersonalData;

use App\Domain\PersonalData\Cpf;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CpfTest extends TestCase
{
    public function test_it_normalizes_formats_and_masks_a_valid_synthetic_cpf(): void
    {
        $cpf = Cpf::from('123.456.789-09');

        $this->assertSame('12345678909', $cpf->digits());
        $this->assertSame('123.456.789-09', $cpf->formatted());
        $this->assertSame('***.456.***-09', $cpf->masked());
        $this->assertSame($cpf->digits(), Cpf::from('12345678909')->digits());
    }

    #[DataProvider('invalidCpfProvider')]
    public function test_it_rejects_invalid_cpfs(string $value): void
    {
        $this->assertFalse(Cpf::isValid($value));

        $this->expectException(InvalidArgumentException::class);
        Cpf::from($value);
    }

    /** @return array<string, array{string}> */
    public static function invalidCpfProvider(): array
    {
        return [
            'wrong check digits' => ['123.456.789-00'],
            'repeated sequence' => ['000.000.000-00'],
            'too short' => ['1234567890'],
            'letters' => ['ABC.456.789-09'],
        ];
    }
}
