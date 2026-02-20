<?php
declare(strict_types=1);

/**
 * Manage manual stock lots by conid in lots.json with file locking.
 */

header('Content-Type: application/json; charset=utf-8');

$lotsFile = __DIR__ . '/lots.json';

if (!file_exists($lotsFile)) {
    file_put_contents($lotsFile, json_encode([], JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo file_get_contents($lotsFile);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['conid']) || !array_key_exists('lots', $input) || !is_array($input['lots'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing conid or lots array']);
        exit;
    }

    $conid = (string)$input['conid'];
    $rawLots = $input['lots'];
    $normalizedLots = [];

    foreach ($rawLots as $i => $rawLot) {
        if (!is_array($rawLot)) {
            http_response_code(400);
            echo json_encode(['error' => "Lot " . ($i + 1) . ' must be an object']);
            exit;
        }

        if (!isset($rawLot['shares']) || !is_numeric($rawLot['shares'])) {
            http_response_code(400);
            echo json_encode(['error' => "Lot " . ($i + 1) . ' shares must be numeric']);
            exit;
        }
        if (!isset($rawLot['price']) || !is_numeric($rawLot['price'])) {
            http_response_code(400);
            echo json_encode(['error' => "Lot " . ($i + 1) . ' price must be numeric']);
            exit;
        }

        $shares = (float)$rawLot['shares'];
        $price = (float)$rawLot['price'];
        $date = isset($rawLot['date']) ? trim((string)$rawLot['date']) : '';

        if ($shares == 0.0) {
            http_response_code(400);
            echo json_encode(['error' => "Lot " . ($i + 1) . ' shares must be non-zero']);
            exit;
        }
        if ($price < 0) {
            http_response_code(400);
            echo json_encode(['error' => "Lot " . ($i + 1) . ' price must be non-negative']);
            exit;
        }
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['error' => "Lot " . ($i + 1) . ' date must use YYYY-MM-DD']);
            exit;
        }

        $lot = [
            'shares' => $shares,
            'price' => $price
        ];
        if ($date !== '') {
            $lot['date'] = $date;
        }

        $normalizedLots[] = $lot;
    }

    $fp = fopen($lotsFile, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not open data file']);
        exit;
    }

    if (flock($fp, LOCK_EX)) {
        $filesize = filesize($lotsFile);
        $content = $filesize > 0 ? fread($fp, $filesize) : '{}';
        $lots = json_decode($content, true);

        if ($lots === null && json_last_error() !== JSON_ERROR_NONE) {
            $lots = [];
        }

        if (count($normalizedLots) === 0) {
            unset($lots[$conid]);
        } else {
            $lots[$conid] = $normalizedLots;
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($lots, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not lock data file']);
        fclose($fp);
        exit;
    }

    fclose($fp);

    echo json_encode(['success' => true, 'lots' => $lots]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
