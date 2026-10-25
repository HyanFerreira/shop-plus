<?php

// Separate explicitly provisioned lab policy. The main PHPUnit guards are NOT used or modified.
require_once dirname(__DIR__, 3).'/vendor/autoload.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Env;

final class SdsLabConnector extends MySqlConnector
{
    public function __construct(private array $lab) {}

    public function connect(array $config)
    {
        foreach (['host', 'port', 'database', 'username', 'password'] as $key) {
            if ((string) ($config[$key] ?? '') !== (string) $this->lab[$key]) {
                throw new RuntimeException('Lab connection target rejected.');
            }
        }
        if (! empty($config['url']) || ! empty($config['unix_socket']) || ! empty($config['options'])) {
            throw new RuntimeException('Lab connection overrides rejected.');
        }
        $pdo = parent::connect($config);
        if ($pdo->query('SELECT @@server_uuid')->fetchColumn() !== $this->lab['server_uuid']) {
            throw new RuntimeException('Lab server identity mismatch; no migration permitted.');
        }

        return $pdo;
    }
}

function sdsLabDescriptor(string $path): array
{
    $lab = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    $expectedRoot = rtrim(getenv('USERPROFILE') ?: getenv('HOME'), '/\\').'/.shopplus-sds7c-'.$lab['owner'];
    if (! preg_match('/^[a-f0-9]{12}$/', $lab['owner'])
        || realpath($lab['root']) !== realpath($expectedRoot)
        || realpath(dirname($path)) !== realpath($expectedRoot)
        || realpath($lab['app_root']) !== realpath($expectedRoot.'/app')
        || $lab['host'] !== '127.0.0.1' || $lab['port'] !== 33077
        || $lab['database'] !== 'shopplus_sds7c_'.$lab['owner']
        || $lab['username'] !== 'shopplus_lab'
        || $lab['container'] !== 'shopplus-sds7c-'.$lab['owner']
        || $lab['network'] !== $lab['container'].'-internal'
        || ! preg_match('/^[a-f0-9]{64}$/', $lab['container_id'])
        || ! preg_match('/^[a-f0-9-]{36}$/', $lab['server_uuid'])) {
        throw new RuntimeException('Not an authorized disposable lab descriptor.');
    }

    return $lab;
}

function sdsLabApplication(string $descriptorPath, bool $boot = false)
{
    $lab = sdsLabDescriptor($descriptorPath);
    foreach (['APP_ENV' => 'sds_lab', 'APP_DEBUG' => 'false', 'APP_KEY' => $lab['app_key'],
        'APP_URL' => $lab['http_url'], 'APP_CONFIG_CACHE' => 'bootstrap/cache/config.php',
        'APP_SERVICES_CACHE' => 'bootstrap/cache/services.php',
        'APP_PACKAGES_CACHE' => 'bootstrap/cache/packages.php',
        'APP_ROUTES_CACHE' => 'bootstrap/cache/routes-v7.php',
        'APP_EVENTS_CACHE' => 'bootstrap/cache/events.php',
        'LARAVEL_STORAGE_PATH' => $lab['app_root'].'/storage',
        'VIEW_COMPILED_PATH' => $lab['app_root'].'/storage/framework/views',
        'DB_CONNECTION' => 'mysql', 'DB_HOST' => $lab['host'], 'DB_PORT' => (string) $lab['port'],
        'DB_DATABASE' => $lab['database'], 'DB_USERNAME' => $lab['username'], 'DB_PASSWORD' => $lab['password'],
        'DB_URL' => '', 'DB_SOCKET' => '', 'PII_BLIND_INDEX_KEY' => $lab['blind_key'],
        'DEMO_PASSWORD' => $lab['demo_password'], 'MAIL_MAILER' => 'array', 'LOG_CHANNEL' => 'null',
        'QUEUE_CONNECTION' => 'sync', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'file',
        'SESSION_ENCRYPT' => 'true', 'SESSION_SECURE_COOKIE' => 'false'] as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $_SERVER[$key] = $value;
        Env::getRepository()->set($key, $value);
    }
    if (is_file($lab['app_root'].'/bootstrap/cache/config.php')) {
        throw new RuntimeException('Cached lab configuration rejected.');
    }
    $app = require $lab['app_root'].'/bootstrap/shopplus.php';
    $app->afterBootstrapping(LoadConfiguration::class, function ($app) use ($lab): void {
        $config = $app['config'];
        $config->set('database.default', 'mysql');
        $config->set('database.connections', ['mysql' => [
            'driver' => 'mysql', 'host' => $lab['host'], 'port' => $lab['port'],
            'database' => $lab['database'], 'username' => $lab['username'], 'password' => $lab['password'],
            'url' => null, 'unix_socket' => '', 'options' => [], 'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true, 'engine' => 'InnoDB',
        ]]);
        $config->set('database.redis', []);
        $config->set('cache.default', 'array');
        $config->set('cache.limiter', 'array');
        $config->set('logging.default', 'null');
        $config->set('mail.default', 'array');
        $config->set('queue.default', 'sync');
        $app->bind('db.connector.mysql', fn () => new SdsLabConnector($lab));
    });
    $app->afterResolving(Factory::class, fn ($http) => $http->preventStrayRequests());
    if ($boot) {
        $app->make(Kernel::class)->bootstrap();
    }

    return $app;
}
