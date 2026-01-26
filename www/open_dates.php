<?php
declare(strict_types=1);

/**
 * Manage position open dates in open_dates.json
 */

header('Content-Type: application/json; charset=utf-8');

$openDatesFile = __DIR__ . '/open_dates.json';

// Initialize file if not exists
if (!file_exists($openDatesFile)) {
    file_put_contents($openDatesFile, json_encode([], JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo file_get_contents($openDatesFile);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['conid']) || !isset($input['open_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing conid or open_date']);
        exit;
    }

    $conid = (string)$input['conid'];
    $openDate = trim($input['open_date']);

    if ($openDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $openDate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
        exit;
    }

    $openDates = json_decode(file_get_contents($openDatesFile), true);
    
    if ($openDate === '') {
        unset($openDates[$conid]);
    } else {
        $openDates[$conid] = $openDate;
    }

    file_put_contents($openDatesFile, json_encode($openDates, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'open_dates' => $openDates]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
