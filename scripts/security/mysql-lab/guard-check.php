<?php

require __DIR__.'/LabBootstrap.php';

$lab = sdsLabDescriptor($argv[1]);
$poison = $lab['root'].'/must-not-load.php';
$marker = $lab['root'].'/must-not-exist.marker';
file_put_contents($poison, '<?php file_put_contents('.var_export($marker, true).', "unsafe"); return [];');
foreach (['APP_CONFIG_CACHE', 'APP_SERVICES_CACHE', 'APP_PACKAGES_CACHE', 'APP_ROUTES_CACHE', 'APP_EVENTS_CACHE'] as $key) {
    putenv($key.'='.$poison);
    $_ENV[$key] = $_SERVER[$key] = $poison;
}
putenv('LARAVEL_STORAGE_PATH='.$lab['root'].'/wrong-storage');
putenv('VIEW_COMPILED_PATH='.$lab['root'].'/wrong-views');
$app = sdsLabApplication($argv[1], true);
$config = $app['config']->get('database.connections.mysql');
$blocked = [];
foreach (['port' => 3306, 'database' => 'shopplus_db', 'host' => 'localhost'] as $key => $value) {
    try {
        (new SdsLabConnector($lab))->connect(array_replace($config, [$key => $value]));
        throw new LogicException('Unsafe target was accepted');
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() !== 'Lab connection target rejected.') {
            throw $exception;
        }
        $blocked[] = $key;
    }
}
if (is_file($marker) || realpath($app->storagePath()) !== realpath($lab['app_root'].'/storage')
    || array_keys($app['config']->get('database.connections')) !== ['mysql']) {
    throw new RuntimeException('Lab filesystem/alias isolation failed');
}
echo json_encode(['status' => 'PASS', 'rejected_before_pdo' => $blocked,
    'poisoned_cache_executed' => false, 'storage_is_private_clone' => true,
    'only_lab_connection_alias' => true], JSON_PRETTY_PRINT).PHP_EOL;
