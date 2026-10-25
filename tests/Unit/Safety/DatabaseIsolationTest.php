<?php

namespace Tests\Unit\Safety;

use PHPUnit\Framework\TestCase;

class DatabaseIsolationTest extends TestCase
{
    public function test_unsafe_connections_are_rejected_without_pdo_or_preparation(): void
    {
        $report = $this->runGate('verify-isolation.php');
        $this->assertSame(0, $report['pdo_attempts']);
        $this->assertSame(0, $report['preparations']);
    }

    public function test_real_phpunit_bootstrap_rejects_unsafe_config_before_lifecycle(): void
    {
        $report = $this->runGate('verify-bootstrap.php');
        foreach ($report['checks'] as $check) {
            $this->assertSame(0, $check['pdo_attempts']);
            $this->assertSame(0, $check['preparations']);
            $this->assertFalse($check['cache_executed']);
        }
        $this->assertFalse($report['persistent_probe_created']);
    }

    public function test_cache_introduced_by_dotenv_is_never_executed(): void
    {
        $report = $this->runGate('verify-dotenv-cache.php');
        $this->assertTrue($report['blocked']);
        $this->assertFalse($report['cache_executed']);
    }

    private function runGate(string $file): array
    {
        $process = proc_open([PHP_BINARY, __DIR__.'/../../Safety/'.$file], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        $this->assertSame(0, $exit, $errors.$output);
        $report = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('PASS', $report['status']);

        return $report;
    }
}
