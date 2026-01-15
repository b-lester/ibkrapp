<?php
declare(strict_types=1);

/**
 * IBKR Client Portal Gateway: List cash balances for all accounts
 */

header('Content-Type: application/json; charset=utf-8');

$GATEWAY_HOST = 'host.docker.internal';
$GATEWAY_PORT = 5050;
$GATEWAY_SCHEME = 'https';
$BASE = "{$GATEWAY_SCHEME}://{$GATEWAY_HOST}:{$GATEWAY_PORT}/v1/api";
$INSECURE_TLS = true;

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
        CURLOPT_COOKIEJAR      => $cookieJarPath,
        CURLOPT_COOKIEFILE     => $cookieJarPath,
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

$cookieJar = sys_get_temp_dir() . '/ibkr_cpg_cookiejar.txt';

try {
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
    $accounts = $acct['accounts'] ?? [];

    if (empty($accounts)) {
        throw new RuntimeException("No accounts returned: " . json_encode($acct));
    }

    $results = [];

    // 3) Cash balances for each account
    foreach ($accounts as $accountId) {
        $accountId = (string)$accountId;
        $ledger = curl_json('GET', "{$BASE}/portfolio/{$accountId}/ledger", $INSECURE_TLS, $cookieJar);
        
        // The ledger response typically contains currency-specific entries.
        // We'll look for "BASE" or aggregate if needed, but usually users want the base currency cash.
        // IBKR returns 'BASE' as a key in the ledger object.
        $results[$accountId] = $ledger;
    }

    echo json_encode([
        'accounts' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
