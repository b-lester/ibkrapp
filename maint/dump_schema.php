<?php

require_once __DIR__ . '/../localconfig.php';

$host = $config['database']['host'];
$username = $config['database']['username'];
$password = $config['database']['password'];
$database = $config['database']['database'];

$outputFile = __DIR__ . '/schema_dump_latest.sql';

$command = sprintf(
    'mysqldump --host=%s --user=%s --password=%s --no-data --routines --triggers %s > %s 2>&1',
    escapeshellarg($host),
    escapeshellarg($username),
    escapeshellarg($password),
    escapeshellarg($database),
    escapeshellarg($outputFile)
);

exec($command, $output, $returnCode);

if ($returnCode === 0) {
    echo "Schema dumped successfully to: $outputFile\n";
} else {
    echo "Error dumping schema (exit code: $returnCode)\n";
    echo implode("\n", $output) . "\n";
}
