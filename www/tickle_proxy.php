<?php
declare(strict_types=1);

/**
 * IBKR Client Portal Gateway: Proxy tickle request to bypass CORS
 */

header('Content-Type: application/json; charset=utf-8');

$GATEWAY_HOST = 'host.docker.internal';
$GATEWAY_PORT = 5050;
$GATEWAY_SCHEME = 'https';
$BASE = "{$GATEWAY_SCHEME}://{$GATEWAY_HOST}:{$GATEWAY_PORT}/v1/api";
$INSECURE_TLS = true;

$cookieJar = sys_get_temp_dir() . '/ibkr_cpg_cookiejar.txt';

$url = "{$BASE}/tickle";
$ch = curl_init($url);

$headers = [
    'Accept: application/json',
    'User-Agent: Console',
];

curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'GET',
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

if ($code === 401) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Not authenticated. Open the gateway login page on the host and log in first.',
        'login_url' => "{$GATEWAY_SCHEME}://localhost:{$GATEWAY_PORT}/",
        'iserver' => [
            'authStatus' => [
                'authenticated' => false,
            ],
        ],
        'upstream' => [
            'service' => 'IBKR Client Portal Gateway',
            'status' => 401,
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($body === false || trim((string)$body) === '' || $code === 0) {
    http_response_code(502);
    echo json_encode([
        'error' => 'IBKR Gateway connection failed.',
        'upstream' => [
            'service' => 'IBKR Client Portal Gateway',
            'status' => $code ?: null,
        ],
        'details' => $curlError ?: null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code($code);
echo $body;
