<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Http\Client\Factory as HttpFactory;
use Monolog\Handler\NullHandler;

final class TestEnvironment
{
    public static function assertEnvironment(array $sources): void
    {
        foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:'] as $key => $expected) {
            $found = false;
            foreach ($sources as $source) {
                if (array_key_exists($key, $source)) {
                    $found = true;
                    if ($source[$key] !== $expected) {
                        throw new UnsafeTestEnvironment('Test isolation rejected environment variable '.$key.'.');
                    }
                }
            }
            if (! $found) {
                throw new UnsafeTestEnvironment('Test isolation requires explicit '.$key.'. Use the project test configuration.');
            }
        }
        foreach ($sources as $source) {
            if (! empty($source['DB_URL'])) {
                throw new UnsafeTestEnvironment('Test isolation rejects database URLs.');
            }
        }
    }

    public static function preflight(string $basePath): void
    {
        $sources = [getenv(), $_ENV, $_SERVER];
        self::assertEnvironment($sources);
        $paths = [$basePath.'/bootstrap/cache/config.php'];
        foreach ($sources as $source) {
            if (! empty($source['APP_CONFIG_CACHE'])) {
                $path = $source['APP_CONFIG_CACHE'];
                $paths[] = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)
                    ? $path : $basePath.'/'.$path;
            }
        }
        foreach ($paths as $path) {
            if (is_file($path)) {
                throw new UnsafeTestEnvironment('Test isolation rejects cached configuration; no cache was executed or removed.');
            }
        }
        if (! is_file($basePath.'/.env.testing')) {
            throw new UnsafeTestEnvironment('Test isolation requires the dedicated .env.testing file.');
        }
    }

    public static function assertConnection(array $config): void
    {
        if (($config['driver'] ?? null) !== 'sqlite' || ($config['database'] ?? null) !== ':memory:' || ! empty($config['url'])) {
            throw new UnsafeTestEnvironment('Only SQLite :memory: without a connection URL is authorized for tests.');
        }
        foreach (['read', 'write', 'options', 'pragmas', 'host', 'username', 'password'] as $key) {
            if (! empty($config[$key])) {
                throw new UnsafeTestEnvironment('Test isolation rejects connection overrides and PDO options.');
            }
        }
    }

    public static function assertApplication($app): void
    {
        $config = $app['config'];
        if ($config->get('app.env') !== 'testing') {
            throw new UnsafeTestEnvironment('Guarded database access requires the testing environment.');
        }
        $name = $config->get('database.default');
        $connections = $config->get('database.connections', []);
        if (! is_string($name) || ! isset($connections[$name])) {
            throw new UnsafeTestEnvironment('Test isolation rejects the default connection.');
        }
        self::assertConnection($connections[$name]);
    }

    public static function assertSql(string $sql): void
    {
        // Runs before Connection::run opens PDO, even on a lazy/cached connection.
        if (preg_match('/\b(?:attach|vacuum|load_extension)\b/i', $sql)) {
            throw new UnsafeTestEnvironment('Persistent SQLite operations are prohibited in tests.');
        }
    }

    public static function protect(Application $app): void
    {
        self::preflight($app->basePath());
        $app->loadEnvironmentFrom('.env.testing');
        $app->beforeBootstrapping(LoadEnvironmentVariables::class, function ($app): void {
            self::preflight($app->basePath());
            if ($app->configurationIsCached()) {
                throw new UnsafeTestEnvironment('Test isolation rejects the effective configuration cache.');
            }
        });
        // Dotenv can introduce a custom cache path. Check again before require/cache.
        $app->beforeBootstrapping(LoadConfiguration::class, function ($app): void {
            self::preflight($app->basePath());
            if ($app->configurationIsCached()) {
                throw new UnsafeTestEnvironment('Test isolation rejects the effective configuration cache.');
            }
        });
        $app->afterBootstrapping(LoadConfiguration::class, function ($app): void {
            self::assertEnvironment([getenv(), $_ENV, $_SERVER]);
            self::assertApplication($app);
            $config = $app['config'];
            $config->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
            $config->set('personal-data.blind_index_key', 'synthetic-automated-test-key-never-use-in-production');
            // Remove dormant persistent aliases and external service transports.
            $default = $config->get('database.default');
            $config->set('database.connections', [$default => $config->get('database.connections.'.$default)]);
            $config->set('database.redis', []);
            $config->set('cache.default', 'array');
            $config->set('cache.stores', ['array' => ['driver' => 'array', 'serialize' => false]]);
            $config->set('cache.limiter', 'array');
            $config->set('session.driver', 'array');
            $config->set('queue.default', 'sync');
            $config->set('queue.connections', ['sync' => ['driver' => 'sync']]);
            $config->set('mail.default', 'array');
            $config->set('mail.mailers', ['array' => ['transport' => 'array']]);
            $config->set('broadcasting.default', 'null');
            $config->set('broadcasting.connections', ['null' => ['driver' => 'null']]);
            $config->set('logging.default', 'null');
            $config->set('logging.channels', ['null' => ['driver' => 'monolog', 'handler' => NullHandler::class]]);
            $root = sys_get_temp_dir().'/shopplus-tests-'.getmypid();
            $config->set('filesystems.default', 'local');
            $config->set('filesystems.disks', [
                'local' => ['driver' => 'local', 'root' => $root.'/private', 'throw' => true],
                'public' => ['driver' => 'local', 'root' => $root.'/public', 'url' => 'http://localhost/storage', 'visibility' => 'public', 'throw' => true],
            ]);
        });
        // Container extenders apply when Laravel registers/resolves these services.
        $app->extend('db.factory', fn ($factory, $app) => new MemoryConnectionFactory($app));
        $app->extend('db', fn ($manager, $app) => new MemoryDatabaseManager($app, $app['db.factory']));
        $app->afterResolving(HttpFactory::class, fn ($http) => $http->preventStrayRequests());
    }
}
