<?php

namespace Tests\Support;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;

class MemoryDatabaseManager extends DatabaseManager
{
    public function connection($name = null)
    {
        TestEnvironment::assertApplication($this->app);
        [$database] = $this->parseConnectionName(\Illuminate\Support\enum_value($name) ?: $this->getDefaultConnection());
        $this->configuration($database); // Check even when an instance is already cached.

        return parent::connection($name);
    }

    protected function configuration($name)
    {
        $config = $this->dynamicConnectionConfigurations[$name] ?? Arr::get($this->app['config']['database.connections'], $name);
        if (! is_array($config)) {
            throw new UnsafeTestEnvironment('Test isolation rejects an unconfigured connection.');
        }
        TestEnvironment::assertConnection($config); // Before URL parsing or extension resolution.

        return parent::configuration($name);
    }

    public function connectUsing(string $name, array $config, bool $force = false)
    {
        TestEnvironment::assertApplication($this->app);
        TestEnvironment::assertConnection($config);

        return parent::connectUsing($name, $config, $force);
    }

    public function reconnect($name = null)
    {
        TestEnvironment::assertApplication($this->app);
        [$database] = $this->parseConnectionName(\Illuminate\Support\enum_value($name) ?: $this->getDefaultConnection());
        $this->configuration($database);

        return parent::reconnect($name);
    }

    public function setDefaultConnection($name)
    {
        $this->configuration(\Illuminate\Support\enum_value($name));
        parent::setDefaultConnection($name);
    }

    public function extend($name, callable $resolver)
    {
        throw new UnsafeTestEnvironment('Custom database resolvers are prohibited in tests.');
    }
}
