<?php

namespace Tests\Unit\Domain\Payment;

use App\Domain\Payment\FictitiousCardValidator;
use Illuminate\Validation\ValidationException;
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
}
