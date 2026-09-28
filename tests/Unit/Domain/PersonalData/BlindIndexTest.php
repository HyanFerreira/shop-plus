<?php

namespace Tests\Unit\Domain\PersonalData;

use App\Domain\PersonalData\BlindIndex;
use RuntimeException;
use Tests\TestCase;

class BlindIndexTest extends TestCase
{
    public function test_it_creates_a_deterministic_keyed_sha256_index(): void
    {
        config()->set('personal-data.blind_index_key', 'synthetic-test-key-one');

        $first = (new BlindIndex)->for('12345678909');
        $second = (new BlindIndex)->for('12345678909');

        $this->assertSame($first, $second);
        $this->assertSame(64, strlen($first));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first);
        $this->assertNotSame(hash('sha256', '12345678909'), $first);
    }

    public function test_different_keys_produce_different_indexes(): void
    {
        config()->set('personal-data.blind_index_key', 'synthetic-test-key-one');
        $first = (new BlindIndex)->for('12345678909');

        config()->set('personal-data.blind_index_key', 'synthetic-test-key-two');
        $second = (new BlindIndex)->for('12345678909');

        $this->assertNotSame($first, $second);
    }

    public function test_it_fails_closed_without_a_key(): void
    {
        config()->set('personal-data.blind_index_key', '');

        $this->expectException(RuntimeException::class);
        (new BlindIndex)->for('12345678909');
    }
}
