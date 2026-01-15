<?php
declare(strict_types=1);

/**
 * IBKR Client Portal Gateway: List positions as JSON
 *
 * Prereqs:
 * - Client Portal Gateway running on your Mac (e.g. https://localhost:5050)
 * - You have logged in via browser once (2FA handled there)
 * - Container can reach host via host.docker.internal
 * - PHP ext: curl, json
 *
 * Usage:
 * - Put this in your web root, hit it in browser, it prints JSON.
 */

header('Content-Type: application/json; charset=utf-8');

$GATEWAY_HOST = 'host.docker.internal';
$GATEWAY_PORT = 5050;                  // <-- CHANGE THIS to your gateway port
$GATEWAY_SCHEME = 'https';             // 'https' or 'http' (depends how you run gateway)
$BASE = "{$GATEWAY_SCHEME}://{$GATEWAY_HOST}:{$GATEWAY_PORT}/v1/api";

// If HTTPS (self-signed), set true to skip TLS verification (dev only)
$INSECURE_TLS = true;

// ---- helpers --------------------------------------------------------------

function curl_json(string $method, string $url, bool $insecureTls, string $cookieJarPath): array {
    $ch = curl_init($url);

    $headers = [
        'Accept: application/json',
        'User-Agent: Console',
    ];

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => $headers,

        // Persist session cookies between calls (required)
        CURLOPT_COOKIEJAR      => $cookieJarPath,
        CURLOPT_COOKIEFILE     => $cookieJarPath,

        // Helpful debug: include headers in error cases if needed
        CURLOPT_HEADER         => false,
    ]);

    if ($insecureTls) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    $body = curl_exec($ch);
    if ($body === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("cURL error: {$err} ({$url})");
    }

    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($code < 200 || $code >= 300) {
        throw new RuntimeException("HTTP {$code} from gateway for {$url}: {$body}");
    }

    return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
}

function require_key(array $arr, string $key, string $context): mixed {
    if (!array_key_exists($key, $arr)) {
        throw new RuntimeException("Missing '{$key}' in {$context} response: " . json_encode($arr));
    }
    return $arr[$key];
}

// ---- main ----------------------------------------------------------------

// Use a writable path inside container. Ensure /tmp is writable.
$cookieJar = sys_get_temp_dir() . '/ibkr_cpg_cookiejar.txt';

// 1) Auth status
$auth = curl_json('GET', "{$BASE}/iserver/auth/status", $INSECURE_TLS, $cookieJar);
if (($auth['authenticated'] ?? false) !== true) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Not authenticated. Open the gateway login page on the host and log in first.',
        'login_url' => "{$GATEWAY_SCHEME}://localhost:{$GATEWAY_PORT}/",
        'auth_status' => $auth,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// 2) Accounts
$acct = curl_json('GET', "{$BASE}/iserver/accounts", $INSECURE_TLS, $cookieJar);
$accounts = require_key($acct, 'accounts', 'iserver/accounts');

if (!is_array($accounts) || count($accounts) === 0) {
    throw new RuntimeException("No accounts returned: " . json_encode($acct));
}

// Choose first account (change if you want a specific one)
$accountId = (string)$accounts[0];

// 3) Positions (paged). Collect all pages.
$all = [];
$page = 0;

while (true) {
    $pos = curl_json('GET', "{$BASE}/portfolio/{$accountId}/positions/{$page}", $INSECURE_TLS, $cookieJar);

    // Response format can vary; commonly it's an array of positions or an object with 'positions'
    $chunk = $pos['positions'] ?? $pos;

    if (!is_array($chunk)) {
        throw new RuntimeException("Unexpected positions response: " . json_encode($pos));
    }

    if (count($chunk) === 0) break;

    $all = array_merge($all, $chunk);
    $page++;
    if ($page > 200) break; // safety
}

echo json_encode([
    'account' => $accountId,
    'count'   => count($all),
    'positions' => $all,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
