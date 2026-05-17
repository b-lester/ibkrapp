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

    $mysqli = mysqli_init();
    if (!$mysqli) {
        throw new Exception('Database connection failed: mysqli_init failed');
    }

    $connectTimeout = (int)($config['database']['connect_timeout'] ?? 3);
    $connectTimeout = max(1, $connectTimeout);
    $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $connectTimeout);
    if (defined('MYSQLI_OPT_READ_TIMEOUT')) {
        $mysqli->options(MYSQLI_OPT_READ_TIMEOUT, $connectTimeout);
    }

    try {
        $connected = @$mysqli->real_connect(
            $config['database']['host'],
            $config['database']['username'],
            $config['database']['password'],
            $config['database']['database']
        );
    } catch (mysqli_sql_exception $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage(), 0, $e);
    }

    if (!$connected) {
        $error = $mysqli->connect_error ?: mysqli_connect_error() ?: 'unknown error';
        throw new Exception('Database connection failed: ' . $error);
    }

    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}
