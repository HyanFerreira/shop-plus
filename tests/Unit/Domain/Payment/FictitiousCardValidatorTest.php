<?php

namespace Tests\Unit\Domain\Payment;

use App\Domain\Payment\FictitiousCardValidator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FictitiousCardValidatorTest extends TestCase
{
    public function test_documented_cards_produce_predictable_results(): void
    {
        $validator = new FictitiousCardValidator;
        $approved = $validator->validate(FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1);
        $declined = $validator->validate(FictitiousCardValidator::DECLINED_VISA, '123', now()->month, now()->year + 1);

        $this->assertTrue($approved['authorized']);
        $this->assertFalse($declined['authorized']);
        $this->assertSame('1111', $approved['last_four']);
    }

    public function test_unknown_card_or_cvv_is_rejected_generically(): void
    {
        $this->expectException(ValidationException::class);
        (new FictitiousCardValidator)->validate('4242424242424242', '12', now()->month, now()->year + 1);
    }

    #[DataProvider('structurallyValidCards')]
    public function test_structurally_valid_test_cards_are_authorized(string $number, string $brand, string $lastFour): void
    {
        $this->assertSame(['authorized' => true, 'brand' => $brand, 'last_four' => $lastFour],
            (new FictitiousCardValidator)->validate($number, '123', now()->month, now()->year + 1));
    }

    public static function structurallyValidCards(): array
    {
        return [
            ['4111 1111 1111 1111', 'visa', '1111'],
            ['5555 5555 5555 4444', 'mastercard', '4444'],
            ['5437 1426 1584 3937', 'mastercard', '3937'],
            ['4242424242424242', 'visa', '4242'],
            ['4222222222222', 'visa', '2222'],
            ['4000 0000 0000 0000 006', 'visa', '0006'],
            ['2221000000000009', 'mastercard', '0009'],
            ['2720990000000007', 'mastercard', '0007'],
        ];
    }

    #[DataProvider('invalidCards')]
    public function test_malformed_or_unsupported_test_cards_are_rejected(string $number, string $cvv): void
    {
        $this->expectException(ValidationException::class);
        (new FictitiousCardValidator)->validate($number, $cvv, now()->month, now()->year + 1);
    }

    public static function invalidCards(): array
    {
        return [
            ['4111x111111111111', '123'], ['4111-1111-1111-1111', '123'],
            ["4111\t111111111111", '123'], ["4111111111111111\n", '123'],
            ['４１１１１１１１１１１１１１１１', '123'],
            ['1111111111111111', '123'], ['0000000000000000', '123'],
            ['4111111111111112', '123'], ['411111111111', '123'],
            ['40000000000000000000', '123'], ['378282246310005', '1234'],
            ['6011111111111117', '123'], ['2220000000000000', '123'],
            ['2721000000000004', '123'], ['4111111111111111', '1234'],
            ['5555555555554444', '１２３'], ['4111111111111111', '12'],
            ['4111111111111111', "123\n"],
        ];
    }

    #[DataProvider('invalidDates')]
    public function test_invalid_dates_are_rejected_before_date_normalization(int $month, int $year): void
    {
        $this->expectException(ValidationException::class);
        (new FictitiousCardValidator)->validate('4111111111111111', '123', $month, $year);
    }

    public static function invalidDates(): array
    {
        return [[0, 2030], [13, 2030], [1, 0], [1, -1], [1, PHP_INT_MAX], [1, 2000], [1, 2100]];
    }

    public function test_current_expiry_month_is_valid_but_previous_month_is_expired(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31)->setTime(23, 59, 59));
        $validator = new FictitiousCardValidator;
        $this->assertTrue($validator->validate('4111111111111111', '123', 10, 2026)['authorized']);
        $this->expectException(ValidationException::class);
        $validator->validate('4111111111111111', '123', 9, 2026);
    }
}
