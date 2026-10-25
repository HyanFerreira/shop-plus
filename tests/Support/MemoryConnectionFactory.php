<?php

namespace Tests\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;

class MemoryConnectionFactory extends ConnectionFactory
{
    public function make(array $config, $name = null)
    {
        TestEnvironment::assertApplication($this->container);
        TestEnvironment::assertConnection($config);
        if (Connection::getResolver('sqlite') !== null) {
            throw new UnsafeTestEnvironment('Static database resolvers are prohibited in tests.');
        }
        $connection = parent::make($config, $name);
        $connection->beforeExecuting(function ($query): void {
            TestEnvironment::assertApplication($this->container);
            TestEnvironment::assertSql($query);
        });

        return $connection;
    }

    public function createConnector(array $config)
    {
        TestEnvironment::assertApplication($this->container);
        TestEnvironment::assertConnection($config);

        return $this->memoryConnector();
    }

    protected function memoryConnector(): MemoryConnector
    {
        return new MemoryConnector($this->container);
    }
}
