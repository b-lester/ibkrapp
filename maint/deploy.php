#!/usr/bin/env php
<?php

/**
 * Database schema deployment script for ibkrapp.
 *
 * This project runs PHP locally, so deployment only means:
 * 1. Apply pending SQL from maint/golive_plan.sql to the configured database.
 * 2. Clear maint/golive_plan.sql after successful application.
 * 3. Dump the updated schema to maint/schema_dump_latest.sql.
 */

function output(string $message): void {
    echo "[deploy] $message\n";
}

function fail(string $message): void {
    output("ERROR: $message");
    exit(1);
}

function runCommand(string $command): array {
    exec($command . ' 2>&1', $output, $returnCode);
    return ['output' => $output, 'code' => $returnCode];
}

function applySql(mysqli $mysqli, string $sql): void {
    if (!$mysqli->multi_query($sql)) {
        fail("SQL error: " . $mysqli->error);
    }

    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());

    if ($mysqli->errno) {
        fail("SQL error: " . $mysqli->error);
    }
}

$root = dirname(__DIR__);
$configPath = $root . '/localconfig.php';
$golivePath = __DIR__ . '/golive_plan.sql';
$dumpScript = __DIR__ . '/dump_schema.php';

if (!file_exists($configPath)) {
    fail("localconfig.php not found. Copy localconfig.example.php and fill in database credentials.");
}

require_once $configPath;
if (!isset($config['database']) || !is_array($config['database'])) {
    fail("Invalid localconfig.php: missing database settings.");
}

if (!file_exists($golivePath)) {
    fail("maint/golive_plan.sql not found.");
}

$sql = file_get_contents($golivePath);
if ($sql === false) {
    fail("Failed to read maint/golive_plan.sql.");
}

$trimmedSql = trim($sql);
if ($trimmedSql === '') {
    output("No SQL migrations to apply.");
} else {
    output("Connecting to database...");
    $mysqli = new mysqli(
        $config['database']['host'],
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['database']
    );

    if ($mysqli->connect_error) {
        fail("Database connection failed: " . $mysqli->connect_error);
    }

    $mysqli->set_charset('utf8mb4');

    output("Applying SQL migrations from maint/golive_plan.sql...");
    applySql($mysqli, $trimmedSql);
    $mysqli->close();

    output("SQL migrations applied successfully.");

    if (file_put_contents($golivePath, '') === false) {
        fail("Failed to clear maint/golive_plan.sql.");
    }
    output("Cleared maint/golive_plan.sql.");
}

output("Dumping updated schema...");
$result = runCommand('php ' . escapeshellarg($dumpScript));
if ($result['code'] !== 0) {
    fail("Schema dump failed:\n" . implode("\n", $result['output']));
}

echo implode("\n", $result['output']);
if (!empty($result['output'])) {
    echo "\n";
}

output("Database schema deployment complete. Review and commit maint/schema_dump_latest.sql and maint/golive_plan.sql.");
