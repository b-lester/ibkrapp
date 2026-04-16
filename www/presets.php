<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$presetsFile = __DIR__ . '/presets.json';

if (!file_exists($presetsFile)) {
    file_put_contents($presetsFile, json_encode(['presets' => []], JSON_PRETTY_PRINT));
}

function readPresets(string $presetsFile): array
{
    $content = file_get_contents($presetsFile);
    if ($content === false || trim($content) === '') {
        return ['presets' => []];
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return ['presets' => []];
    }

    if (!isset($decoded['presets']) || !is_array($decoded['presets'])) {
        $decoded['presets'] = [];
    }

    return $decoded;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(readPresets($presetsFile));
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }

    $name = trim((string)($input['name'] ?? ''));
    $sort = (string)($input['sort'] ?? '');
    $filter = (string)($input['filter'] ?? '');
    $groupByTicker = (bool)($input['groupByTicker'] ?? false);

    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Preset name is required']);
        exit;
    }

    $allowedSorts = ['ticker', 'expires', 'pnl', 'exposure'];
    $allowedFilters = ['all', 'STK', 'OPT'];

    if (!in_array($sort, $allowedSorts, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid sort value']);
        exit;
    }

    if (!in_array($filter, $allowedFilters, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid filter value']);
        exit;
    }

    $fp = fopen($presetsFile, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not open data file']);
        exit;
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        http_response_code(500);
        echo json_encode(['error' => 'Could not lock data file']);
        exit;
    }

    $filesize = filesize($presetsFile);
    $content = $filesize > 0 ? fread($fp, $filesize) : '';
    $data = json_decode($content, true);
    if (!is_array($data)) {
        $data = ['presets' => []];
    }
    if (!isset($data['presets']) || !is_array($data['presets'])) {
        $data['presets'] = [];
    }

    $presets = array_values(array_filter(
        $data['presets'],
        static fn(array $preset): bool => (string)($preset['name'] ?? '') !== $name
    ));

    $presets[] = [
        'name' => $name,
        'sort' => $sort,
        'filter' => $filter,
        'groupByTicker' => $groupByTicker
    ];

    $data['presets'] = $presets;

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    echo json_encode(['success' => true, 'presets' => $data['presets']]);
    exit;
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }

    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Preset name is required']);
        exit;
    }

    $fp = fopen($presetsFile, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not open data file']);
        exit;
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        http_response_code(500);
        echo json_encode(['error' => 'Could not lock data file']);
        exit;
    }

    $filesize = filesize($presetsFile);
    $content = $filesize > 0 ? fread($fp, $filesize) : '';
    $data = json_decode($content, true);
    if (!is_array($data)) {
        $data = ['presets' => []];
    }
    if (!isset($data['presets']) || !is_array($data['presets'])) {
        $data['presets'] = [];
    }

    $data['presets'] = array_values(array_filter(
        $data['presets'],
        static fn(array $preset): bool => (string)($preset['name'] ?? '') !== $name
    ));

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    echo json_encode(['success' => true, 'presets' => $data['presets']]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
