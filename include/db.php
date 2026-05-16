<?php

/**
 * Database connection helper
 * Returns a mysqli connection using config from localconfig.php
 */

date_default_timezone_set('America/Los_Angeles');

$configPath = dirname(__DIR__) . '/localconfig.php';
if (!file_exists($configPath)) {
    throw new Exception('Configuration file not found');
}

require_once $configPath;
if (!isset($config['database']) || !is_array($config['database'])) {
    throw new Exception('Invalid configuration: missing database settings');
}

function getDbConnection(): mysqli {
    global $config;

    $mysqli = new mysqli(
        $config['database']['host'],
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['database']
    );

    if ($mysqli->connect_error) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }

    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}
