<?php
declare(strict_types=1);

/**
 * IBKR Client Portal Gateway: Proxy logout request to bypass CORS
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Unsupported method. Use POST.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$GATEWAY_HOST = 'host.docker.internal';
$GATEWAY_PORT = 5050;
$GATEWAY_SCHEME = 'https';
$BASE = "{$GATEWAY_SCHEME}://{$GATEWAY_HOST}:{$GATEWAY_PORT}/v1/api";
$INSECURE_TLS = true;

$cookieJar = sys_get_temp_dir() . '/ibkr_cpg_cookiejar.txt';
$url = "{$BASE}/logout";

$ch = curl_init($url);
$headers = [
    'Accept: application/json',
    'User-Agent: Console',
];

curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => '',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_COOKIEJAR      => $cookieJar,
    CURLOPT_COOKIEFILE     => $cookieJar,
]);

if ($INSECURE_TLS) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
}

$body = curl_exec($ch);
$curlError = curl_error($ch);
$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if (is_file($cookieJar)) {
    @unlink($cookieJar);
}

if ($body === false || $code === 0) {
    http_response_code(502);
    echo json_encode([
        'error' => 'IBKR Gateway logout failed.',
        'upstream' => [
            'service' => 'IBKR Client Portal Gateway',
            'status' => $code ?: null,
        ],
        'details' => $curlError ?: null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code($code);
if (trim((string)$body) === '') {
    echo json_encode(['loggedOut' => $code >= 200 && $code < 300], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

echo $body;
