<?php
declare(strict_types=1);

/**
 * IBKR Client Portal Gateway: Historical market data as chart-ready JSON
 *
 * Example:
 * GET example:
 * /marketdata.php?conid=265598&period=1d&bar=5min&exchange=SMART&outsideRth=false
 * /marketdata.php?symbol=NOW&period=1d&bar=5min&exchange=SMART&outsideRth=false
 *
 * POST example JSON body:
 * {"conid":"265598","period":"1d","bar":"5min","exchange":"SMART","outsideRth":false}
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$appRoot = dirname(__DIR__);
$dbHelperPath = $appRoot . '/include/db.php';
$localConfigPath = $appRoot . '/localconfig.php';
if (file_exists($dbHelperPath) && file_exists($localConfigPath)) {
    require_once $dbHelperPath;
}

$GATEWAY_HOST = 'host.docker.internal';
$GATEWAY_PORT = 5050;
$GATEWAY_SCHEME = 'https';
$BASE = "{$GATEWAY_SCHEME}://{$GATEWAY_HOST}:{$GATEWAY_PORT}/v1/api";
$INSECURE_TLS = true;

class GatewayHttpException extends RuntimeException {
    public int $statusCode;
    public string $responseBody;

    public function __construct(int $statusCode, string $message, string $responseBody = '') {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        parent::__construct($message, $statusCode);
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
        throw new GatewayHttpException($code, "HTTP {$code} from gateway for {$url}", $body);
    }

    return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
}

function request_data(): array {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        return $_GET;
    }

    if ($method !== 'POST') {
        throw new InvalidArgumentException('Unsupported method. Use GET or POST.');
    }

    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    if (strpos($contentType, 'application/json') !== false) {
        $rawBody = file_get_contents('php://input');
        if ($rawBody === false || trim($rawBody) === '') {
            return [];
        }

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Invalid JSON body.');
        }

        return $decoded;
    }

    return $_POST;
}

function request_string(array $input, string $key, ?string $default = null): ?string {
    $value = $input[$key] ?? $default;
    if ($value === null) return null;
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function request_bool(array $input, string $key, bool $default): bool {
    $value = $input[$key] ?? null;
    if ($value === null || $value === '') return $default;
    if (is_bool($value)) return $value;
    $normalized = strtolower(trim((string)$value));
    return in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true);
}

function request_int(array $input, string $key, int $default, int $min, int $max): int {
    $value = $input[$key] ?? null;
    if ($value === null || $value === '') return $default;
    if (!is_numeric($value)) {
        throw new InvalidArgumentException("Invalid {$key}.");
    }

    $intValue = (int)$value;
    if ($intValue < $min || $intValue > $max) {
        throw new InvalidArgumentException("Invalid {$key}. Use {$min}-{$max}.");
    }

    return $intValue;
}

function validate_period(string $period): void {
    if (!preg_match('/^([1-9][0-9]{0,2}|1000)(min|h|d|w|m|y)$/', $period, $matches)) {
        throw new InvalidArgumentException('Invalid period. Use values like 30min, 1h, 1d, 1w, 6m, or 1y.');
    }

    $amount = (int)$matches[1];
    $unit = $matches[2];
    $maxByUnit = [
        'min' => 30,
        'h' => 8,
        'd' => 1000,
        'w' => 792,
        'm' => 182,
        'y' => 15,
    ];

    if ($amount > $maxByUnit[$unit]) {
        throw new InvalidArgumentException("Invalid period. Maximum for {$unit} is {$maxByUnit[$unit]}{$unit}.");
    }
}

function normalize_bar(array $bar): array {
    $timestampMs = isset($bar['t']) ? (int)$bar['t'] : null;

    return [
        'time' => $timestampMs,
        'isoTime' => $timestampMs !== null ? gmdate('c', (int)floor($timestampMs / 1000)) : null,
        'open' => isset($bar['o']) ? (float)$bar['o'] : null,
        'high' => isset($bar['h']) ? (float)$bar['h'] : null,
        'low' => isset($bar['l']) ? (float)$bar['l'] : null,
        'close' => isset($bar['c']) ? (float)$bar['c'] : null,
        'volume' => isset($bar['v']) ? (float)$bar['v'] : null,
    ];
}

function candidate_has_sec_type(array $candidate, string $secType): bool {
    foreach (($candidate['sections'] ?? []) as $section) {
        $types = explode(',', (string)($section['secType'] ?? ''));
        if (in_array($secType, array_map('trim', $types), true)) {
            return true;
        }
    }

    return false;
}

function resolve_symbol(string $base, string $symbol, string $secType, bool $insecureTls, string $cookieJar): array {
    if (!preg_match('/^[A-Za-z0-9._ -]+$/', $symbol)) {
        throw new InvalidArgumentException('Invalid symbol.');
    }

    $url = "{$base}/iserver/secdef/search?" . http_build_query([
        'symbol' => $symbol,
        'name' => 'false',
    ]);
    $candidates = curl_json('GET', $url, $insecureTls, $cookieJar);

    if (!is_array($candidates) || count($candidates) === 0) {
        throw new InvalidArgumentException("No contract found for symbol {$symbol}.");
    }

    $normalizedSymbol = strtoupper($symbol);
    $exactMatches = array_values(array_filter($candidates, function ($candidate) use ($normalizedSymbol) {
        return strtoupper((string)($candidate['symbol'] ?? '')) === $normalizedSymbol;
    }));

    $searchPool = count($exactMatches) > 0 ? $exactMatches : $candidates;
    foreach ($searchPool as $candidate) {
        if (candidate_has_sec_type($candidate, $secType) && isset($candidate['conid'])) {
            return $candidate;
        }
    }

    foreach ($searchPool as $candidate) {
        if (isset($candidate['conid'])) {
            return $candidate;
        }
    }

    throw new InvalidArgumentException("No usable contract found for symbol {$symbol}.");
}

function get_optional_db_connection(): ?mysqli {
    if (!function_exists('getDbConnection')) return null;

    try {
        return getDbConnection();
    } catch (Throwable $e) {
        return null;
    }
}

function marketdata_cache_key(array $parts): string {
    ksort($parts);
    return hash('sha256', json_encode($parts, JSON_UNESCAPED_SLASHES));
}

function load_cached_history(mysqli $db, string $cacheKey, int $maxAgeSeconds): ?array {
    $stmt = $db->prepare("
        SELECT response_json, fetched_at
        FROM marketdata_history_cache
        WHERE cache_key = ?
        LIMIT 1
    ");
    if (!$stmt) return null;

    $stmt->bind_param('s', $cacheKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) return null;

    $fetchedAt = (int)$row['fetched_at'];
    if ($maxAgeSeconds > 0 && (time() - $fetchedAt) > $maxAgeSeconds) {
        return null;
    }

    $payload = json_decode((string)$row['response_json'], true);
    if (!is_array($payload)) return null;

    return [
        'payload' => $payload,
        'fetched_at' => $fetchedAt,
    ];
}

function save_cached_history(
    mysqli $db,
    string $cacheKey,
    string $conid,
    ?string $symbol,
    string $secType,
    ?string $exchange,
    string $period,
    string $bar,
    ?string $startTime,
    bool $outsideRth,
    string $source,
    array $history
): void {
    $responseJson = json_encode($history, JSON_UNESCAPED_SLASHES);
    if ($responseJson === false) return;

    $fetchedAt = time();
    $outsideRthInt = $outsideRth ? 1 : 0;
    $conidInt = (int)$conid;

    $stmt = $db->prepare("
        INSERT INTO marketdata_history_cache
            (cache_key, conid, symbol, sec_type, exchange, period_value, bar_value, start_time, outside_rth, source_value, response_json, fetched_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            conid = VALUES(conid),
            symbol = VALUES(symbol),
            sec_type = VALUES(sec_type),
            exchange = VALUES(exchange),
            period_value = VALUES(period_value),
            bar_value = VALUES(bar_value),
            start_time = VALUES(start_time),
            outside_rth = VALUES(outside_rth),
            source_value = VALUES(source_value),
            response_json = VALUES(response_json),
            fetched_at = VALUES(fetched_at)
    ");
    if (!$stmt) return;

    $stmt->bind_param(
        'sissssssissi',
        $cacheKey,
        $conidInt,
        $symbol,
        $secType,
        $exchange,
        $period,
        $bar,
        $startTime,
        $outsideRthInt,
        $source,
        $responseJson,
        $fetchedAt
    );
    $stmt->execute();
    $stmt->close();
}

function format_history_response(
    array $history,
    array $request,
    bool $cacheHit,
    ?int $cachedAt,
    ?string $cacheKey,
    bool $cacheAvailable
): array {
    $rawBars = is_array($history['data'] ?? null) ? $history['data'] : [];

    return [
        'request' => $request,
        'cache' => [
            'hit' => $cacheHit,
            'available' => $cacheAvailable,
            'key' => $cacheKey,
            'cachedAt' => $cachedAt,
            'cachedAtIso' => $cachedAt !== null ? gmdate('c', $cachedAt) : null,
        ],
        'metadata' => [
            'serverId' => $history['serverId'] ?? null,
            'symbol' => $history['symbol'] ?? null,
            'text' => $history['text'] ?? null,
            'priceFactor' => $history['priceFactor'] ?? null,
            'startTime' => $history['startTime'] ?? null,
            'timePeriod' => $history['timePeriod'] ?? null,
            'barLength' => $history['barLength'] ?? null,
            'mdAvailability' => $history['mdAvailability'] ?? null,
            'mktDataDelay' => $history['mktDataDelay'] ?? null,
            'outsideRth' => $history['outsideRth'] ?? null,
            'volumeFactor' => $history['volumeFactor'] ?? null,
            'points' => $history['points'] ?? count($rawBars),
            'travelTime' => $history['travelTime'] ?? null,
        ],
        'count' => count($rawBars),
        'bars' => array_map('normalize_bar', $rawBars),
        'raw' => $history,
    ];
}

try {
    $input = request_data();

    $conidInput = request_string($input, 'conid');
    $symbol = request_string($input, 'symbol');
    $conid = null;
    if ($conidInput !== null) {
        if (!preg_match('/^\d+$/', $conidInput)) {
            throw new InvalidArgumentException('Invalid conid. Use a numeric IBKR contract id, or pass ticker symbols with symbol=.');
        }

        $conid = $conidInput;
    }

    $period = request_string($input, 'period', '1w');
    $bar = request_string($input, 'bar', '1d');
    $exchange = request_string($input, 'exchange', 'SMART');
    $startTime = request_string($input, 'startTime');
    $outsideRth = request_bool($input, 'outsideRth', false);
    $force = request_bool($input, 'force', false);
    $cacheTtl = request_int($input, 'cacheTtl', 900, 0, 86400);
    $source = request_string($input, 'source', 'Trades');
    $secType = request_string($input, 'secType', 'STK');

    if ($conid === null && $symbol === null) {
        throw new InvalidArgumentException('Missing conid or symbol.');
    }

    $validBars = ['1min', '2min', '3min', '5min', '10min', '15min', '30min', '1h', '2h', '3h', '4h', '8h', '1d', '1w', '1m'];
    if (!in_array($bar, $validBars, true)) {
        throw new InvalidArgumentException('Invalid bar. Supported values: ' . implode(', ', $validBars) . '.');
    }

    validate_period($period);

    $validSources = ['Trades', 'Midpoint', 'Bid_Ask'];
    if (!in_array($source, $validSources, true)) {
        throw new InvalidArgumentException('Invalid source. Supported values: ' . implode(', ', $validSources) . '.');
    }

    $validSecTypes = ['STK', 'ETF', 'OPT', 'FUT', 'IND', 'CASH', 'CFD', 'WAR'];
    if (!in_array($secType, $validSecTypes, true)) {
        throw new InvalidArgumentException('Invalid secType. Supported values: ' . implode(', ', $validSecTypes) . '.');
    }

    if ($exchange !== null && !preg_match('/^[A-Za-z0-9._-]+$/', $exchange)) {
        throw new InvalidArgumentException('Invalid exchange.');
    }

    if ($startTime !== null && !preg_match('/^\d{8}-\d{2}:\d{2}:\d{2}$/', $startTime)) {
        throw new InvalidArgumentException('Invalid startTime. Use YYYYMMDD-HH:mm:ss.');
    }

    $cookieJar = sys_get_temp_dir() . '/ibkr_cpg_cookiejar.txt';

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

    // Keeps the brokerage session warm and mirrors the preflight style used by other /iserver calls.
    curl_json('GET', "{$BASE}/iserver/accounts", $INSECURE_TLS, $cookieJar);

    $resolvedContract = null;
    if ($conid === null && $symbol !== null) {
        $resolvedContract = resolve_symbol($BASE, $symbol, $secType, $INSECURE_TLS, $cookieJar);
        $conid = (string)$resolvedContract['conid'];
    }

    $cacheKey = marketdata_cache_key([
        'conid' => $conid,
        'exchange' => $exchange,
        'period' => $period,
        'bar' => $bar,
        'startTime' => $startTime,
        'outsideRth' => $outsideRth,
        'source' => $source,
    ]);

    $requestPayload = [
        'conid' => $conid,
        'exchange' => $exchange,
        'period' => $period,
        'bar' => $bar,
        'startTime' => $startTime,
        'outsideRth' => $outsideRth,
        'source' => $source,
        'symbol' => $symbol,
        'secType' => $secType,
        'resolvedContract' => $resolvedContract,
        'force' => $force,
        'cacheTtl' => $cacheTtl,
    ];

    $db = get_optional_db_connection();
    if ($db !== null && !$force) {
        $cached = load_cached_history($db, $cacheKey, $cacheTtl);
        if ($cached !== null) {
            echo json_encode(
                format_history_response($cached['payload'], $requestPayload, true, $cached['fetched_at'], $cacheKey, true),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );
            exit;
        }
    }

    $query = [
        'conid' => $conid,
        'period' => $period,
        'bar' => $bar,
        'outsideRth' => $outsideRth ? 'true' : 'false',
        'source' => $source,
    ];

    if ($exchange !== null) $query['exchange'] = $exchange;
    if ($startTime !== null) $query['startTime'] = $startTime;

    $historyUrl = "{$BASE}/iserver/marketdata/history?" . http_build_query($query);
    $history = curl_json('GET', $historyUrl, $INSECURE_TLS, $cookieJar);
    if ($db !== null) {
        save_cached_history($db, $cacheKey, $conid, $symbol, $secType, $exchange, $period, $bar, $startTime, $outsideRth, $source, $history);
    }

    echo json_encode(
        format_history_response($history, $requestPayload, false, null, $cacheKey, $db !== null),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (GatewayHttpException $e) {
    http_response_code($e->statusCode);
    $gatewayBody = json_decode($e->responseBody, true);
    echo json_encode([
        'error' => $e->getMessage(),
        'gateway_response' => $gatewayBody ?? $e->responseBody,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
