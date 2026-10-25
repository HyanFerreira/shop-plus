<?php

namespace Tests\Feature\Testing;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemoryEnvironmentTest extends TestCase
{
    public function test_booted_application_only_opens_disposable_memory_database(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(['sqlite'], array_keys(config('database.connections')));
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->assertSame('sqlite', DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertSame('', DB::select('PRAGMA database_list')[0]->file);
        DB::statement('CREATE TABLE isolation_positive (id INTEGER PRIMARY KEY)');
        DB::statement('INSERT INTO isolation_positive (id) VALUES (1)');
        $this->assertSame(1, DB::table('isolation_positive')->count());
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('session.driver'));
        $this->assertSame('sync', config('queue.default'));
        $this->assertSame('array', config('mail.default'));
    }
}
