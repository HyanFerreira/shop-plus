<?php

// Standalone safety gate: no Laravel application boot, RefreshDatabase or real PDO.
require dirname(__DIR__, 2).'/vendor/autoload.php';

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Tests\Support\MemoryConnectionFactory;
use Tests\Support\MemoryConnector;
use Tests\Support\MemoryDatabaseManager;
use Tests\Support\TestEnvironment;
use Tests\Support\UnsafeTestEnvironment;

if (! class_exists(TestEnvironment::class)) {
    echo json_encode(['status' => 'RED', 'reason' => 'Isolation guard is not implemented', 'pdo_attempts' => 0, 'preparations' => 0]).PHP_EOL;
    exit(1);
}

$attempts = 0;
$preparations = 0;
$checks = [];
$memory = ['driver' => 'sqlite', 'database' => ':memory:', 'url' => null, 'prefix' => ''];
$app = new Container;
$reset = function () use ($app, $memory): void {
    $app->instance('config', new Repository(['app' => ['env' => 'testing'], 'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => $memory]]]));
};
$reset();
$factory = new class($app, $attempts) extends MemoryConnectionFactory
{
    public function __construct($app, private int &$attempts)
    {
        parent::__construct($app);
    }

    protected function memoryConnector(): MemoryConnector
    {
        return new class($this->container, $this->attempts) extends MemoryConnector
        {
            public function __construct($app, private int &$attempts)
            {
                parent::__construct($app);
            }

            protected function connectMemory(array $config): PDO
            {
                $this->attempts++;
                throw new RuntimeException('PDO probe reached; real connection never attempted');
            }
        };
    }
};
$blocked = function (string $name, callable $operation) use (&$checks, &$attempts, &$preparations): void {
    try {
        $operation();
        $preparations++;
        throw new RuntimeException('Guard did not block: '.$name);
    } catch (UnsafeTestEnvironment $error) {
        if ($attempts !== 0 || $preparations !== 0) {
            throw new RuntimeException('Unsafe preparation or connector attempt');
        }
        $checks[] = ['scenario' => $name, 'blocked' => true, 'pdo_attempts' => $attempts, 'preparations' => $preparations];
    }
};

foreach ([
    'mysql' => ['driver' => 'mysql', 'database' => 'shopplus_db'],
    'pgsql' => ['driver' => 'pgsql', 'database' => 'synthetic'],
    'sqlite-file' => ['driver' => 'sqlite', 'database' => '/synthetic/must-not-open.sqlite'],
    'sqlite-uri' => ['driver' => 'sqlite', 'database' => 'file::memory:?cache=shared'],
    'conflicting-url' => $memory + ['unused' => null],
    'read-write-override' => $memory + ['read' => ['database' => '/synthetic/file']],
    'pdo-options' => $memory + ['options' => [PDO::ATTR_PERSISTENT => true]],
] as $name => $config) {
    if ($name === 'conflicting-url') {
        $config['url'] = 'mysql://synthetic:synthetic@invalid.invalid/forbidden';
    }
    $blocked($name, fn () => $factory->make($config)->getPdo());
}

$manager = new MemoryDatabaseManager($app, $factory);
$app['config']->set('database.connections.forbidden', ['driver' => 'mysql', 'database' => 'shopplus_db']);
$blocked('alternative-alias', fn () => $manager->connection('forbidden')->getPdo());
$blocked('set-default', fn () => $manager->setDefaultConnection('forbidden'));
$blocked('dynamic-build', fn () => $manager->build(['driver' => 'mysql', 'database' => 'shopplus_db'])->getPdo());
$blocked('connect-using', fn () => $manager->connectUsing('forbidden', ['driver' => 'pgsql', 'database' => 'synthetic'])->getPdo());
$blocked('custom-resolver', fn () => $manager->extend('sqlite', fn () => throw new RuntimeException('Resolver reached')));
$app['config']->set('database.default', 'forbidden');
$blocked('changed-default-config', fn () => $manager->connection()->getPdo());
$reset();
$connection = $manager->connection(); // Lazy only; spy connector has not been called.
$app['config']->set('database.connections.sqlite.database', '/synthetic/file');
$blocked('changed-config-cached-connection', fn () => $manager->connection()->getPdo());
$blocked('changed-config-reconnect', fn () => $manager->reconnect());
$blocked('changed-config-lazy-pdo', fn () => $connection->getPdo());
$reset();
foreach (['ATTACH DATABASE \'/synthetic/file\' AS other', 'VACUUM INTO \'/synthetic/file\'', "SELECT load_extension('/synthetic/module')"] as $index => $sql) {
    $blocked('persistent-sql-'.$index, fn () => $connection->statement($sql));
}

$safe = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => ''];
foreach (['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'shopplus_db', 'DB_URL' => 'pgsql://invalid.invalid/synthetic', 'APP_ENV' => 'local'] as $key => $value) {
    $bad = array_replace($safe, [$key => $value]);
    $blocked('environment-'.$key, fn () => TestEnvironment::assertEnvironment([$safe, $bad]));
}
TestEnvironment::assertEnvironment([$safe]);
TestEnvironment::assertConnection($memory);
$checks[] = ['scenario' => 'safe-memory-validation', 'accepted_without_pdo' => true];
// Last: static Laravel resolver registration has no public unregister API.
Connection::resolverFor('sqlite', function () use (&$attempts) {
    $attempts++;
    throw new RuntimeException('Static resolver reached; no real PDO was opened');
});
$blocked('static-sqlite-resolver', fn () => $factory->make($memory));
echo json_encode(['status' => 'PASS', 'checks' => $checks, 'pdo_attempts' => $attempts, 'preparations' => $preparations], JSON_PRETTY_PRINT).PHP_EOL;
