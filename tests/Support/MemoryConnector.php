<?php

namespace Tests\Support;

use Illuminate\Database\Connectors\SQLiteConnector;
use PDO;

class MemoryConnector extends SQLiteConnector
{
    public function __construct(private $app) {}

    public function connect(array $config)
    {
        TestEnvironment::assertApplication($this->app);
        TestEnvironment::assertConnection($config);

        return $this->connectMemory($config);
    }

    protected function connectMemory(array $config): PDO
    {
        return parent::connect($config);
    }
}
