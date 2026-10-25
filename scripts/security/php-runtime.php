<?php

// Read-only inventory. Run via CLI; never publish diagnostic endpoints.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$minimums = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];
$branch = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
$patched = isset($minimums[$branch]) && version_compare(PHP_VERSION, $minimums[$branch], '>=');
$mitigated = ! filter_var(ini_get('session.upload_progress.enabled'), FILTER_VALIDATE_BOOLEAN);
$extensions = get_loaded_extensions();
sort($extensions);

echo json_encode([
    'scope' => 'CLI only; does not attest an existing HTTP worker or VM',
    'version' => PHP_VERSION,
    'binary' => PHP_BINARY,
    'sapi' => PHP_SAPI,
    'ini' => php_ini_loaded_file(),
    'scanned_ini' => php_ini_scanned_files(),
    'extensions' => $extensions,
    'session' => [
        'upload_progress_enabled' => ini_get('session.upload_progress.enabled'),
        'serialize_handler' => ini_get('session.serialize_handler'),
        'use_strict_mode' => ini_get('session.use_strict_mode'),
        'use_only_cookies' => ini_get('session.use_only_cookies'),
    ],
    'cve_2026_109618_109619_version_check' => $patched ? 'meets advisory minimum' : 'FAIL: unpatched or unrecognized branch',
    'cve_2026_115560' => $mitigated ? 'upload progress mitigation active' : 'mitigation absent; inspect serializer switching',
    'advisory_as_of' => '2026-10-24; recheck official advisories before deployment',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

exit($patched && $mitigated ? 0 : 1);
