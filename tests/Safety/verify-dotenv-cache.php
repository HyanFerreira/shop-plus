<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Tests\Support\TestEnvironment;
use Tests\Support\UnsafeTestEnvironment;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$root = sys_get_temp_dir().'/shopplus-dotenv-cache-'.bin2hex(random_bytes(8));
mkdir($root, 0700, true);
$marker = $root.'/cache-executed';
file_put_contents($root.'/unsafe.php', '<?php file_put_contents('.var_export($marker, true).', "probe"); return ["app" => ["env" => "testing"], "database" => ["default" => "mysql"]];');
file_put_contents($root.'/.env.testing', 'APP_CONFIG_CACHE=unsafe.php'.PHP_EOL);
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => ''] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
putenv('APP_CONFIG_CACHE');
unset($_ENV['APP_CONFIG_CACHE'], $_SERVER['APP_CONFIG_CACHE']);
$app = new Application($root);
$blocked = false;
try {
    TestEnvironment::protect($app);
    $app->bootstrapWith([LoadEnvironmentVariables::class, LoadConfiguration::class]);
} catch (UnsafeTestEnvironment $error) {
    $blocked = true;
}
$report = ['status' => $blocked && ! is_file($marker) ? 'PASS' : 'FAIL', 'blocked' => $blocked, 'cache_executed' => is_file($marker), 'actual_database_setup' => 'none: this probe contains no PDO or migrations'];
echo json_encode($report).PHP_EOL;
exit($blocked && ! $report['cache_executed'] ? 0 : 1);
