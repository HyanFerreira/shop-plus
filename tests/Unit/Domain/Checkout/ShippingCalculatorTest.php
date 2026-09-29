<?php

namespace Tests\Unit\Domain\Checkout;

use App\Domain\Checkout\ShippingCalculator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShippingCalculatorTest extends TestCase
{
    public function test_options_are_deterministic_and_use_weight_and_postal_range(): void
    {
        $local = (new ShippingCalculator)->options('01001-000', 1500);
        $remote = (new ShippingCalculator)->options('70000-000', 1500);

        $this->assertSame(1690, $local['economy']['price_cents']);
        $this->assertSame(0, $local['pickup']['price_cents']);
        $this->assertSame(1000, $remote['economy']['price_cents'] - $local['economy']['price_cents']);
    }

    public function test_invalid_postal_code_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new ShippingCalculator)->options('invalid', 1000);
    }
}
