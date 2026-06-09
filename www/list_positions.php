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
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$GATEWAY_HOST = 'host.docker.internal';
$GATEWAY_PORT = 5050;                  // <-- CHANGE THIS to your gateway port
$GATEWAY_SCHEME = 'https';             // 'https' or 'http' (depends how you run gateway)
$BASE = "{$GATEWAY_SCHEME}://{$GATEWAY_HOST}:{$GATEWAY_PORT}/v1/api";

// If HTTPS (self-signed), set true to skip TLS verification (dev only)
$INSECURE_TLS = true;
$localConfig = [];
$localConfigPath = dirname(__DIR__) . '/localconfig.php';
if (file_exists($localConfigPath)) {
    require $localConfigPath;
    if (isset($config) && is_array($config)) {
        $localConfig = $config;
    }
}

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

function discover_account_ids(string $baseUrl, bool $insecureTls, string $cookieJar, array $config, ?array &$debug = null): array {
    $accountIds = [];
    $debug = [];

    $sources = [
        'portfolioAccounts' => "{$baseUrl}/portfolio/accounts",
        'portfolioSubaccounts' => "{$baseUrl}/portfolio/subaccounts",
        'portfolioSubaccounts2' => "{$baseUrl}/portfolio/subaccounts2?page=0",
        'allocationAccounts' => "{$baseUrl}/iserver/account/allocation/accounts",
    ];

    foreach ($sources as $sourceName => $url) {
        $before = array_keys($accountIds);

        try {
            $response = curl_json('GET', $url, $insecureTls, $cookieJar);
            add_account_ids_from_response($response, $accountIds);
            $after = array_keys($accountIds);
            $debug[$sourceName] = [
                'ok' => true,
                'accounts' => array_values(array_diff($after, $before)),
            ];
        } catch (Throwable $e) {
            $debug[$sourceName] = [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    try {
        $iserverAccounts = curl_json('GET', "{$baseUrl}/iserver/accounts", $insecureTls, $cookieJar);
        $accounts = require_key($iserverAccounts, 'accounts', 'iserver/accounts');
        $before = array_keys($accountIds);
        add_account_ids_from_response($accounts, $accountIds);
        $after = array_keys($accountIds);
        $debug['iserverAccounts'] = [
            'ok' => true,
            'accounts' => array_values(array_diff($after, $before)),
        ];
    } catch (Throwable $e) {
        $debug['iserverAccounts'] = [
            'ok' => false,
            'error' => $e->getMessage(),
        ];
    }

    $before = array_keys($accountIds);
    add_configured_account_ids($config, $accountIds);
    $after = array_keys($accountIds);
    $debug['configuredAccounts'] = [
        'ok' => true,
        'accounts' => array_values(array_diff($after, $before)),
    ];

    return array_keys($accountIds);
}

function fetch_positions_for_account(string $accountId, string $baseUrl, bool $insecureTls, string $cookieJar): array {
    $all = [];
    $page = 0;

    while (true) {
        $safeAccountId = rawurlencode($accountId);
        $pos = curl_json('GET', "{$baseUrl}/portfolio/{$safeAccountId}/positions/{$page}", $insecureTls, $cookieJar);

        // Response format can vary; commonly it's an array of positions or an object with 'positions'
        $chunk = $pos['positions'] ?? $pos;

        if (!is_array($chunk)) {
            throw new RuntimeException("Unexpected positions response: " . json_encode($pos));
        }

        if (count($chunk) === 0) break;

        foreach ($chunk as $position) {
            if (is_array($position) && !isset($position['acctId'])) {
                $position['acctId'] = $accountId;
            }
            $all[] = $position;
        }

        $page++;
        if ($page > 200) break; // safety
    }

    return $all;
}

function number_value(mixed $value): float {
    return is_numeric($value) ? (float)$value : 0.0;
}

function market_data_number(mixed $value): ?float {
    if ($value === null) return null;

    $text = trim((string)$value);
    if ($text === '') return null;

    $text = str_replace(',', '', $text);
    if (strlen($text) > 1 && strtoupper($text[0]) === 'C') {
        $text = substr($text, 1);
    }

    return is_numeric($text) ? (float)$text : null;
}

function fetch_marketdata_snapshots(array $conids, string $baseUrl, bool $insecureTls, string $cookieJar): array {
    $snapshots = [];
    $conids = array_values(array_unique(array_filter(array_map('strval', $conids), fn($conid) => $conid !== '')));
    if (count($conids) === 0) return $snapshots;

    foreach (array_chunk($conids, 50) as $chunk) {
        $query = http_build_query([
            'conids' => implode(',', $chunk),
            'fields' => '31,84,86',
        ]);
        $url = "{$baseUrl}/iserver/marketdata/snapshot?{$query}";

        $response = curl_json('GET', $url, $insecureTls, $cookieJar);
        if (is_array($response)) {
            foreach ($response as $snapshot) {
                if (!is_array($snapshot) || !isset($snapshot['conid'])) continue;
                $snapshots[(string)$snapshot['conid']] = $snapshot;
            }
        }

        $missingBid = false;
        foreach ($chunk as $conid) {
            if (!isset($snapshots[$conid]['84'])) {
                $missingBid = true;
                break;
            }
        }

        if ($missingBid) {
            usleep(250000);
            $response = curl_json('GET', $url, $insecureTls, $cookieJar);
            if (is_array($response)) {
                foreach ($response as $snapshot) {
                    if (!is_array($snapshot) || !isset($snapshot['conid'])) continue;
                    $snapshots[(string)$snapshot['conid']] = $snapshot;
                }
            }
        }
    }

    return $snapshots;
}

function add_option_bid_quotes(array &$positions, string $baseUrl, bool $insecureTls, string $cookieJar): void {
    $optionConids = [];
    foreach ($positions as $position) {
        if (!is_array($position) || ($position['assetClass'] ?? '') !== 'OPT' || empty($position['conid'])) continue;
        $optionConids[] = (string)$position['conid'];
    }

    try {
        $snapshots = fetch_marketdata_snapshots($optionConids, $baseUrl, $insecureTls, $cookieJar);
    } catch (Throwable $e) {
        return;
    }

    if (count($snapshots) === 0) return;

    foreach ($positions as &$position) {
        if (!is_array($position) || ($position['assetClass'] ?? '') !== 'OPT' || empty($position['conid'])) continue;

        $snapshot = $snapshots[(string)$position['conid']] ?? null;
        if (!is_array($snapshot)) continue;

        $bid = market_data_number($snapshot['84'] ?? null);
        if ($bid !== null) {
            $position['quoteBid'] = $bid;
        }
    }
    unset($position);
}

function aggregate_positions(array $positions): array {
    $groups = [];

    foreach ($positions as $position) {
        if (!is_array($position)) continue;

        $key = isset($position['conid']) && $position['conid'] !== ''
            ? 'conid:' . (string)$position['conid']
            : 'desc:' . (string)($position['contractDesc'] ?? '');

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'position' => $position,
                'weightedAvgPrice' => 0.0,
                'weightedAvgCost' => 0.0,
                'weight' => 0.0,
                'accounts' => [],
            ];
            $groups[$key]['position']['acctId'] = 'Aggregate';
            $groups[$key]['position']['accounts'] = [];
            $groups[$key]['position']['position'] = 0.0;
            $groups[$key]['position']['mktValue'] = 0.0;
            $groups[$key]['position']['realizedPnl'] = 0.0;
            $groups[$key]['position']['unrealizedPnl'] = 0.0;
        }

        $qty = number_value($position['position'] ?? 0);
        $weight = abs($qty);
        $acctId = (string)($position['acctId'] ?? '');

        $groups[$key]['position']['position'] += $qty;
        $groups[$key]['position']['mktValue'] += number_value($position['mktValue'] ?? 0);
        $groups[$key]['position']['realizedPnl'] += number_value($position['realizedPnl'] ?? 0);
        $groups[$key]['position']['unrealizedPnl'] += number_value($position['unrealizedPnl'] ?? 0);
        $groups[$key]['position']['mktPrice'] = number_value($position['mktPrice'] ?? ($groups[$key]['position']['mktPrice'] ?? 0));
        $groups[$key]['weightedAvgPrice'] += number_value($position['avgPrice'] ?? 0) * $weight;
        $groups[$key]['weightedAvgCost'] += number_value($position['avgCost'] ?? 0) * $weight;
        $groups[$key]['weight'] += $weight;

        if ($acctId !== '') {
            $groups[$key]['accounts'][$acctId] = true;
        }
    }

    $aggregated = [];
    foreach ($groups as $group) {
        if ($group['weight'] > 0) {
            $group['position']['avgPrice'] = $group['weightedAvgPrice'] / $group['weight'];
            $group['position']['avgCost'] = $group['weightedAvgCost'] / $group['weight'];
        }
        $group['position']['accounts'] = array_keys($group['accounts']);
        $aggregated[] = $group['position'];
    }

    return $aggregated;
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

// 2) Portfolio accounts. IBKR requires /portfolio/accounts before /portfolio endpoints.
$accountDiscovery = [];
$accountIds = discover_account_ids($BASE, $INSECURE_TLS, $cookieJar, $localConfig, $accountDiscovery);
if (count($accountIds) === 0) {
    throw new RuntimeException("No portfolio accounts returned.");
}
$requestedAccount = isset($_GET['account']) ? trim((string)$_GET['account']) : 'all';
$selectedAccount = $requestedAccount === '' ? 'all' : $requestedAccount;

$invalidRequestedAccount = null;
if ($selectedAccount !== 'all' && !in_array($selectedAccount, $accountIds, true)) {
    $invalidRequestedAccount = $selectedAccount;
    $selectedAccount = 'all';
}

// 3) Positions (paged). Collect all pages for the selected account or every account.
$rawPositions = [];
if ($selectedAccount === 'all') {
    foreach ($accountIds as $accountId) {
        $rawPositions = array_merge($rawPositions, fetch_positions_for_account($accountId, $BASE, $INSECURE_TLS, $cookieJar));
    }
    $all = aggregate_positions($rawPositions);
} else {
    $all = fetch_positions_for_account($selectedAccount, $BASE, $INSECURE_TLS, $cookieJar);
    $rawPositions = $all;
}

add_option_bid_quotes($all, $BASE, $INSECURE_TLS, $cookieJar);

echo json_encode([
    'account' => $selectedAccount,
    'selectedAccount' => $selectedAccount,
    'requestedAccount' => $requestedAccount,
    'invalidRequestedAccount' => $invalidRequestedAccount,
    'accounts' => $accountIds,
    'accountDiscovery' => $accountDiscovery,
    'count'   => count($all),
    'rawCount' => count($rawPositions),
    'positions' => $all,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
