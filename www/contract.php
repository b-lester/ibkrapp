<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

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

function request_string(string $key, ?string $default = null): ?string {
    $value = $_GET[$key] ?? $default;
    if ($value === null) return null;
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function curl_json(string $method, string $url, bool $insecureTls, string $cookieJarPath): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Console'],
        CURLOPT_COOKIEJAR => $cookieJarPath,
        CURLOPT_COOKIEFILE => $cookieJarPath,
        CURLOPT_HEADER => false,
    ]);

    if ($insecureTls) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("cURL error: {$error}");
    }

    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($status < 200 || $status >= 300) {
        throw new GatewayHttpException($status, "Gateway returned HTTP {$status}.", $body);
    }

    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : [];
}

function first_string(array $source, array $keys): string {
    foreach ($keys as $key) {
        if (!isset($source[$key])) continue;
        $value = trim((string)$source[$key]);
        if ($value !== '') return $value;
    }
    return '';
}

function candidate_has_sec_type(array $candidate, string $secType): bool {
    foreach (($candidate['sections'] ?? []) as $section) {
        $types = explode(',', (string)($section['secType'] ?? ''));
        if (in_array($secType, array_map('trim', $types), true)) return true;
    }
    return false;
}

function best_search_candidate(array $candidates, string $symbol, string $secType): ?array {
    $normalizedSymbol = strtoupper($symbol);
    $exactMatches = array_values(array_filter($candidates, function ($candidate) use ($normalizedSymbol) {
        return strtoupper((string)($candidate['symbol'] ?? '')) === $normalizedSymbol;
    }));

    $pool = count($exactMatches) > 0 ? $exactMatches : $candidates;
    foreach ($pool as $candidate) {
        if (is_array($candidate) && candidate_has_sec_type($candidate, $secType) && isset($candidate['conid'])) return $candidate;
    }
    foreach ($pool as $candidate) {
        if (is_array($candidate) && isset($candidate['conid'])) return $candidate;
    }
    return null;
}

function normalize_contract(array $contract, ?string $symbolFallback, ?string $secTypeFallback, ?string $exchangeFallback): array {
    $symbol = first_string($contract, ['symbol', 'ticker', 'localSymbol']) ?: (string)($symbolFallback ?? '');
    $name = first_string($contract, [
        'companyName',
        'company_name',
        'companyHeader',
        'contract_description_1',
        'description',
        'desc',
        'name',
        'text',
    ]);

    if ($name !== '') {
        $name = preg_replace('/\s*\([^)]*\)\s*$/', '', $name) ?? $name;
        $name = trim($name);
    }

    return [
        'conid' => first_string($contract, ['conid', 'con_id']),
        'symbol' => strtoupper($symbol),
        'name' => strtoupper($name) !== strtoupper($symbol) ? $name : '',
        'secType' => first_string($contract, ['secType', 'sec_type']) ?: (string)($secTypeFallback ?? ''),
        'exchange' => first_string($contract, ['exchange', 'listing_exchange']) ?: (string)($exchangeFallback ?? ''),
        'raw' => $contract,
    ];
}

try {
    $symbol = request_string('symbol');
    $conid = request_string('conid');
    $secType = request_string('secType', 'STK') ?? 'STK';
    $exchange = request_string('exchange', 'SMART');

    if ($symbol !== null && !preg_match('/^[A-Za-z0-9._ -]+$/', $symbol)) {
        throw new InvalidArgumentException('Invalid symbol.');
    }
    if ($conid !== null && !preg_match('/^\d+$/', $conid)) {
        throw new InvalidArgumentException('Invalid conid.');
    }
    if ($symbol === null && $conid === null) {
        throw new InvalidArgumentException('Missing symbol or conid.');
    }

    $cookieJar = sys_get_temp_dir() . '/ibkr_cpg_cookiejar.txt';
    $auth = curl_json('GET', "{$BASE}/iserver/auth/status", $INSECURE_TLS, $cookieJar);
    if (($auth['authenticated'] ?? false) !== true) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Not authenticated. Open the gateway login page on the host and log in first.',
            'authenticated' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    curl_json('GET', "{$BASE}/iserver/accounts", $INSECURE_TLS, $cookieJar);

    $contract = null;
    if ($conid !== null) {
        try {
            $contract = curl_json('GET', "{$BASE}/iserver/contract/" . rawurlencode($conid) . '/info', $INSECURE_TLS, $cookieJar);
        } catch (GatewayHttpException $e) {
            $contract = null;
        }
    }

    if ($contract === null && $symbol !== null) {
        $url = "{$BASE}/iserver/secdef/search?" . http_build_query([
            'symbol' => $symbol,
            'name' => 'false',
        ]);
        $candidates = curl_json('GET', $url, $INSECURE_TLS, $cookieJar);
        $contract = best_search_candidate($candidates, $symbol, $secType);
    }

    if (!is_array($contract)) {
        throw new InvalidArgumentException('No contract metadata found.');
    }

    echo json_encode([
        'contract' => normalize_contract($contract, $symbol, $secType, $exchange),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    $status = $e instanceof InvalidArgumentException ? 400 : 502;
    if ($e instanceof GatewayHttpException) {
        $status = $e->statusCode === 401 ? 401 : 502;
    }
    http_response_code($status);
    echo json_encode([
        'error' => $e->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
