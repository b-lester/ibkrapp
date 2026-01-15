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
$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

http_response_code($code);
echo $body;
