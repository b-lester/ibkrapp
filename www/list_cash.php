<?php
declare(strict_types=1);

/**
 * IBKR Client Portal Gateway: List cash balances for all accounts
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$GATEWAY_HOST = 'host.docker.internal';
$GATEWAY_PORT = 5050;
$GATEWAY_SCHEME = 'https';
$BASE = "{$GATEWAY_SCHEME}://{$GATEWAY_HOST}:{$GATEWAY_PORT}/v1/api";
$INSECURE_TLS = true;
$localConfig = [];
$localConfigPath = dirname(__DIR__) . '/localconfig.php';
if (file_exists($localConfigPath)) {
    require $localConfigPath;
    if (isset($config) && is_array($config)) {
        $localConfig = $config;
    }
}

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

function account_id_from_portfolio_account(mixed $account): ?string {
    if (is_string($account) || is_int($account)) {
        $accountId = trim((string)$account);
        if (strcasecmp($accountId, 'all') === 0) return null;
        return $accountId === '' ? null : $accountId;
    }

    if (!is_array($account)) return null;

    foreach (['accountId', 'id', 'accountVan', 'acctId'] as $key) {
        if (isset($account[$key])) {
            $accountId = trim((string)$account[$key]);
            if ($accountId !== '' && strcasecmp($accountId, 'all') !== 0) {
                return $accountId;
            }
        }
    }

    return null;
}

function add_account_ids_from_response(mixed $response, array &$accountIds): void {
    if (is_array($response) && array_key_exists('accounts', $response) && is_array($response['accounts'])) {
        $response = $response['accounts'];
    } elseif (is_array($response) && array_key_exists('subaccounts', $response) && is_array($response['subaccounts'])) {
        $response = $response['subaccounts'];
    }

    if (!is_array($response)) return;

    foreach ($response as $account) {
        $accountId = account_id_from_portfolio_account($account);
        if ($accountId !== null) {
            $accountIds[$accountId] = true;
        }
    }
}

function add_configured_account_ids(array $config, array &$accountIds): void {
    $configuredAccounts = $config['ibkr_accounts'] ?? $config['ibkrAccounts'] ?? [];
    if (!is_array($configuredAccounts)) return;

    foreach ($configuredAccounts as $account) {
        $accountId = account_id_from_portfolio_account($account);
        if ($accountId !== null) {
            $accountIds[$accountId] = true;
        }
    }
}

function discover_account_ids(string $baseUrl, bool $insecureTls, string $cookieJar, array $config): array {
    $accountIds = [];

    $sources = [
        "{$baseUrl}/portfolio/accounts",
        "{$baseUrl}/portfolio/subaccounts",
        "{$baseUrl}/portfolio/subaccounts2?page=0",
        "{$baseUrl}/iserver/account/allocation/accounts",
        "{$baseUrl}/iserver/accounts",
    ];

    foreach ($sources as $url) {
        try {
            $response = curl_json('GET', $url, $insecureTls, $cookieJar);
            add_account_ids_from_response($response, $accountIds);
        } catch (Throwable) {
            // Try the next account-discovery endpoint.
        }
    }

    add_configured_account_ids($config, $accountIds);

    return array_keys($accountIds);
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

    // 2) Portfolio accounts. IBKR requires /portfolio/accounts before /portfolio endpoints.
    $accounts = discover_account_ids($BASE, $INSECURE_TLS, $cookieJar, $localConfig);

    if (empty($accounts)) {
        throw new RuntimeException("No portfolio accounts returned.");
    }

    $results = [];
    $errors = [];

    // 3) Cash balances for each account
    foreach ($accounts as $accountId) {
        $accountId = (string)$accountId;
        try {
            $safeAccountId = rawurlencode($accountId);
            $ledger = curl_json('GET', "{$BASE}/portfolio/{$safeAccountId}/ledger", $INSECURE_TLS, $cookieJar);

            // The ledger response typically contains currency-specific entries.
            // We'll look for "BASE" or aggregate if needed, but usually users want the base currency cash.
            // IBKR returns 'BASE' as a key in the ledger object.
            $results[$accountId] = $ledger;
        } catch (Throwable $e) {
            $errors[$accountId] = $e->getMessage();
        }
    }

    echo json_encode([
        'accounts' => $results,
        'errors' => $errors,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
