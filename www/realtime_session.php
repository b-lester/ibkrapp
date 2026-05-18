<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$gatewayHost = 'host.docker.internal';
$gatewayPort = 5050;
$gatewayScheme = 'https';
$base = "{$gatewayScheme}://{$gatewayHost}:{$gatewayPort}/v1/api";
$cookieJarPath = sys_get_temp_dir() . '/ibkr_cpg_cookiejar.txt';

function read_api_cookie(string $cookieJarPath): ?string {
    if (!is_readable($cookieJarPath)) return null;

    $lines = file($cookieJarPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return null;

    foreach (array_reverse($lines) as $line) {
        if ($line === '' || $line[0] === '#') continue;
        $parts = preg_split('/\s+/', $line);
        if (!is_array($parts) || count($parts) < 7) continue;
        $name = $parts[5] ?? '';
        $value = $parts[6] ?? '';
        if ($name === 'api' && $value !== '') return $value;
    }

    return null;
}

function gateway_json(string $url, string $cookieJarPath): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_COOKIEJAR => $cookieJarPath,
        CURLOPT_COOKIEFILE => $cookieJarPath,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Console'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException($error);
    }

    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("Gateway returned HTTP {$status}.");
    }

    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : [];
}

try {
    $auth = gateway_json("{$base}/iserver/auth/status", $cookieJarPath);
    $apiCookie = read_api_cookie($cookieJarPath);
    if ($apiCookie === null) {
        $tickle = gateway_json("{$base}/tickle", $cookieJarPath);
        if (isset($tickle['session']) && is_string($tickle['session']) && $tickle['session'] !== '') {
            $apiCookie = $tickle['session'];
        }
    }

    if (($auth['authenticated'] ?? false) !== true) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Not authenticated. Open the gateway login page on the host and log in first.',
            'authenticated' => false,
            'auth_status' => $auth,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($apiCookie === null) {
        http_response_code(503);
        echo json_encode([
            'error' => 'Gateway session cookie is unavailable.',
            'authenticated' => true,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode([
        'authenticated' => true,
        'apiCookie' => $apiCookie,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(502);
    echo json_encode([
        'error' => $e->getMessage(),
        'authenticated' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
