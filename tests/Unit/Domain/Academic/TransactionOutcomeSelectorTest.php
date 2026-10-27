<?php

namespace Tests\Unit\Domain\Academic;

use App\Domain\Academic\TransactionOutcomeSelector;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionOutcomeSelectorTest extends TestCase
{
    #[DataProvider('drawBoundaries')]
    public function test_configured_probability_boundaries(int $draw, string $expected): void
    {
        config(['academic-automation.probabilities' => ['approved' => 70, 'declined' => 20, 'pending' => 10]]);
        $this->assertSame($expected, app(TransactionOutcomeSelector::class)->forDraw($draw));
    }

    public static function drawBoundaries(): array
    {
        return [[1, 'approved'], [70, 'approved'], [71, 'declined'], [90, 'declined'], [91, 'pending'], [100, 'pending']];
    }

    #[DataProvider('singleOutcomes')]
    public function test_zero_weight_outcomes_are_never_selected(array $weights, string $expected): void
    {
        config(['academic-automation.probabilities' => $weights]);
        $selector = app(TransactionOutcomeSelector::class);
        $this->assertSame($expected, $selector->forDraw(1));
        $this->assertSame($expected, $selector->forDraw(100));
        $this->assertSame($expected, $selector->choose());
    }

    public static function singleOutcomes(): array
    {
        return [
            [['approved' => 100, 'declined' => 0, 'pending' => 0], 'approved'],
            [['approved' => 0, 'declined' => 100, 'pending' => 0], 'declined'],
            [['approved' => 0, 'declined' => 0, 'pending' => 100], 'pending'],
        ];
    }

    #[DataProvider('invalidConfigurations')]
    public function test_invalid_probability_configuration_is_rejected(mixed $weights): void
    {
        config(['academic-automation.probabilities' => $weights]);
        $this->expectException(InvalidArgumentException::class);
        app(TransactionOutcomeSelector::class)->forDraw(1);
    }

    public static function invalidConfigurations(): array
    {
        return [
            [null], [[]], [['approved' => 100]],
            [['approved' => 70, 'declined' => 20, 'pending' => 9]],
            [['approved' => 101, 'declined' => 0, 'pending' => -1]],
            [['approved' => '70', 'declined' => 20, 'pending' => 10]],
            [['approved' => 70.0, 'declined' => 20, 'pending' => 10]],
            [['approved' => true, 'declined' => 89, 'pending' => 10]],
        ];
    }

    #[DataProvider('invalidDraws')]
    public function test_draw_must_be_in_the_inclusive_one_to_one_hundred_range(int $draw): void
    {
        config(['academic-automation.probabilities' => ['approved' => 70, 'declined' => 20, 'pending' => 10]]);
        $this->expectException(InvalidArgumentException::class);
        app(TransactionOutcomeSelector::class)->forDraw($draw);
    }

    public static function invalidDraws(): array
    {
        return [[0], [-1], [101], [PHP_INT_MAX]];
    }
}
