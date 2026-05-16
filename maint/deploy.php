#!/usr/bin/env php
<?php

/**
 * Remote database schema deployment script for ibkrapp.
 *
 * PHP code runs locally for this project. Schema deployment connects to the
 * remote host, executes pending SQL from maint/golive_plan.sql there, dumps the
 * updated schema, copies that dump back, and clears local golive_plan.sql.
 */

$remoteUser = 'dh_vps_user';
$remoteHost = 'brets.app';
$remoteWorkDir = 'ibkrapp_schema_deploy';

function output(string $message): void {
    echo "[deploy] $message\n";
}

function fail(string $message): void {
    output("ERROR: $message");
    exit(1);
}

function runCommand(string $command, bool $passthru = false): array {
    if ($passthru) {
        passthru($command, $returnCode);
        return ['output' => [], 'code' => $returnCode];
    }

    exec($command . ' 2>&1', $output, $returnCode);
    return ['output' => $output, 'code' => $returnCode];
}

function requireSuccess(array $result, string $message): void {
    if ($result['code'] !== 0) {
        $details = empty($result['output']) ? '' : "\n" . implode("\n", $result['output']);
        fail($message . $details);
    }
}

function validateDatabaseConfig(array $database): void {
    foreach (['host', 'username', 'password', 'database'] as $key) {
        if (!array_key_exists($key, $database) || trim((string)$database[$key]) === '') {
            fail("Invalid localconfig.php: missing database {$key}.");
        }
    }

    if (preg_match('/\s/', (string)$database['host'])) {
        fail("Invalid localconfig.php: database host must be only the hostname, not hostname plus database name.");
    }

    if (preg_match('/\s/', (string)$database['database'])) {
        fail("Invalid localconfig.php: database name must not contain whitespace.");
    }
}

$root = dirname(__DIR__);
$localConfigPath = $root . '/localconfig.php';
$golivePath = __DIR__ . '/golive_plan.sql';
$dumpScriptPath = __DIR__ . '/dump_schema.php';
$schemaDumpPath = __DIR__ . '/schema_dump_latest.sql';

if (!file_exists($localConfigPath)) {
    fail("localconfig.php not found. Copy localconfig.example.php and fill in database credentials.");
}

if (!file_exists($golivePath)) {
    fail("maint/golive_plan.sql not found.");
}

if (!file_exists($dumpScriptPath)) {
    fail("maint/dump_schema.php not found.");
}

require_once $localConfigPath;
if (!isset($config['database']) || !is_array($config['database'])) {
    fail("Invalid localconfig.php: missing database settings.");
}
validateDatabaseConfig($config['database']);

$sql = file_get_contents($golivePath);
if ($sql === false) {
    fail("Failed to read maint/golive_plan.sql.");
}

if (trim($sql) === '') {
    output("No SQL migrations to apply.");
    exit(0);
}

$remoteTarget = "{$remoteUser}@{$remoteHost}";
$remoteDirArg = escapeshellarg($remoteWorkDir);

output("Preparing remote schema deployment workspace on {$remoteTarget}...");
$result = runCommand(sprintf(
    'ssh %s %s',
    escapeshellarg($remoteTarget),
    escapeshellarg("rm -rf {$remoteWorkDir} && mkdir -p {$remoteWorkDir}/maint")
));
requireSuccess($result, "Failed to prepare remote workspace.");

output("Copying schema deployment files to remote host...");
$result = runCommand(sprintf(
    'scp %s %s %s %s:%s/',
    escapeshellarg($localConfigPath),
    escapeshellarg($golivePath),
    escapeshellarg($dumpScriptPath),
    escapeshellarg($remoteTarget),
    $remoteDirArg
));
requireSuccess($result, "Failed to copy deployment files to remote host.");

$remoteCommands = <<<BASH
set -e
cd {$remoteWorkDir}
mkdir -p maint
mv golive_plan.sql maint/golive_plan.sql
mv dump_schema.php maint/dump_schema.php

echo "[remote] Applying SQL migrations..."
php -r '
    require_once "localconfig.php";
    \$host = \$config["database"]["host"];
    \$user = \$config["database"]["username"];
    \$pass = \$config["database"]["password"];
    \$db = \$config["database"]["database"];
    \$sql = file_get_contents("maint/golive_plan.sql");
    if (trim((string) \$sql) === "") {
        echo "No SQL migrations to apply after trimming whitespace.\\n";
        exit(0);
    }
    \$mysqli = new mysqli(\$host, \$user, \$pass, \$db);
    if (\$mysqli->connect_error) {
        fwrite(STDERR, "Connection failed: " . \$mysqli->connect_error . "\\n");
        exit(1);
    }
    \$mysqli->set_charset("utf8mb4");
    if (\$mysqli->multi_query(\$sql)) {
        do {
            if (\$result = \$mysqli->store_result()) {
                \$result->free();
            }
        } while (\$mysqli->more_results() && \$mysqli->next_result());
    }
    if (\$mysqli->errno) {
        fwrite(STDERR, "SQL error: " . \$mysqli->error . "\\n");
        exit(1);
    }
    \$mysqli->close();
    echo "SQL migrations applied successfully.\\n";
'

echo "[remote] Running schema dump..."
php maint/dump_schema.php
BASH;

output("Applying schema changes on remote host...");
$result = runCommand(sprintf(
    'ssh %s %s',
    escapeshellarg($remoteTarget),
    escapeshellarg($remoteCommands)
), true);
requireSuccess($result, "Remote schema deployment failed.");

output("Copying updated schema dump back...");
$result = runCommand(sprintf(
    'scp %s:%s %s',
    escapeshellarg($remoteTarget),
    escapeshellarg($remoteWorkDir . '/maint/schema_dump_latest.sql'),
    escapeshellarg($schemaDumpPath)
));
requireSuccess($result, "Failed to copy schema dump back from remote host.");

output("Cleaning up remote workspace...");
$result = runCommand(sprintf(
    'ssh %s %s',
    escapeshellarg($remoteTarget),
    escapeshellarg("rm -rf {$remoteWorkDir}")
));
requireSuccess($result, "Failed to clean up remote workspace.");

if (file_put_contents($golivePath, '') === false) {
    fail("Failed to clear local maint/golive_plan.sql.");
}

output("Cleared local maint/golive_plan.sql.");
output("Remote database schema deployment complete. Review and commit maint/schema_dump_latest.sql and maint/golive_plan.sql.");
