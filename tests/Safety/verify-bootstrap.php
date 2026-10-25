<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

$repo = dirname(__DIR__, 2);
$directory = sys_get_temp_dir().'/shopplus-bootstrap-probe-'.bin2hex(random_bytes(8));
mkdir($directory, 0700, true);
$safe = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => '', 'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='];
$scenarios = [
    'direct-mysql' => ['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'synthetic_forbidden'],
    'direct-file' => ['DB_DATABASE' => $directory.'/must-not-exist.sqlite'],
    'direct-url' => ['DB_URL' => 'mysql://synthetic:synthetic@invalid.invalid/synthetic'],
    'without-project-bootstrap' => ['DB_CONNECTION' => 'pgsql'],
    'missing-explicit-config' => ['APP_ENV' => false, 'DB_CONNECTION' => false, 'DB_DATABASE' => false],
    'custom-cache' => ['APP_CONFIG_CACHE' => $directory.'/unsafe-config.php'],
    'artisan-parent-mysql' => ['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'synthetic_forbidden', 'DB_HOST' => 'invalid.invalid', 'DB_USERNAME' => 'synthetic', 'DB_PASSWORD' => 'synthetic'],
    'artisan-global-env' => ['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'synthetic_forbidden', 'DB_HOST' => 'invalid.invalid', 'DB_USERNAME' => 'synthetic', 'DB_PASSWORD' => 'synthetic'],
    'artisan-abbreviation' => ['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'synthetic_forbidden', 'DB_HOST' => 'invalid.invalid', 'DB_USERNAME' => 'synthetic', 'DB_PASSWORD' => 'synthetic'],
];
file_put_contents($directory.'/unsafe-config.php', '<?php file_put_contents('.var_export($directory.'/cache-executed', true).', "probe"); return ["database" => ["default" => "mysql"]];');
$results = [];
foreach ($scenarios as $name => $overrides) {
    $marker = $directory.'/'.$name;
    $env = getenv();
    foreach (['APP_ENV', 'DB_CONNECTION', 'DB_DATABASE', 'DB_URL', 'APP_CONFIG_CACHE', 'DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
        unset($env[$key]);
    }
    $env = array_replace($env, $safe, ['ISOLATION_PROBE_MARKER' => $marker], $overrides);
    foreach ($env as $key => $value) {
        if ($value === false) {
            unset($env[$key]);
        }
    }
    $bootstrap = $name === 'without-project-bootstrap' ? 'vendor/autoload.php' : 'tests/bootstrap.php';
    $command = [PHP_BINARY, 'vendor/phpunit/phpunit/phpunit', '--no-configuration', '--do-not-cache-result', '--colors=never', '--bootstrap', $bootstrap, 'tests/Safety/fixtures/IsolationProbeTest.php'];
    if ($name === 'artisan-parent-mysql') {
        $command = [PHP_BINARY, 'artisan', 'test', 'tests/Safety/fixtures/IsolationProbeTest.php', '--compact'];
    }
    if ($name === 'artisan-global-env') {
        $command = [PHP_BINARY, 'artisan', '--env', 'testing', 'test', 'tests/Safety/fixtures/IsolationProbeTest.php', '--compact'];
    }
    if ($name === 'artisan-abbreviation') {
        $command = [PHP_BINARY, 'artisan', 'tes', 'tests/Safety/fixtures/IsolationProbeTest.php', '--compact'];
    }
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $repo, $env);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    $result = ['scenario' => $name, 'exit_nonzero' => $exit !== 0, 'guard_message' => str_contains($output, 'Test isolation'), 'pdo_attempts' => (int) is_file($marker.'.pdo'), 'preparations' => (int) is_file($marker.'.preparation'), 'cache_executed' => is_file($directory.'/cache-executed')];
    $parentBlocked = ! str_starts_with($name, 'artisan-') || (bool) preg_match('/artisan(?:\(\d+\)|:\d+)/', $output);
    $result['parent_guard_when_artisan'] = $parentBlocked;
    $results[] = $result;
    if (! $result['exit_nonzero'] || ! $result['guard_message'] || ! $parentBlocked || $result['pdo_attempts'] || $result['preparations'] || $result['cache_executed']) {
        echo json_encode(['status' => 'FAIL', 'checks' => $results], JSON_PRETTY_PRINT).PHP_EOL;
        exit(1);
    }
}
echo json_encode(['status' => 'PASS', 'checks' => $results, 'persistent_probe_created' => is_file($directory.'/must-not-exist.sqlite')], JSON_PRETTY_PRINT).PHP_EOL;
