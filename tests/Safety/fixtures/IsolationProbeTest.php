<?php

// Discovered only by the standalone negative runner, never by the normal suite.
use Illuminate\Database\Connection;
use Tests\TestCase;

foreach (['mysql', 'pgsql', 'sqlite'] as $driver) {
    Connection::resolverFor($driver, static function () {
        file_put_contents(getenv('ISOLATION_PROBE_MARKER').'.pdo', 'probe');
        throw new RuntimeException('PDO tripwire: no real connector is allowed in this probe.');
    });
}

class IsolationProbeTest extends TestCase
{
    protected function setUpTraits()
    {
        file_put_contents(getenv('ISOLATION_PROBE_MARKER').'.preparation', 'probe');
        throw new RuntimeException('Preparation tripwire: no migrations or database setup exist in this probe.');
    }

    public function test_guard_must_abort_before_reaching_this_test(): void
    {
        $this->fail('Unsafe bootstrap was not rejected.');
    }
}
