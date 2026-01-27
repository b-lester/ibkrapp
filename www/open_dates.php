<?php
declare(strict_types=1);

/**
 * Manage position open dates in open_dates.json with file locking to prevent race conditions.
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

    // Use file locking for atomic updates
    $fp = fopen($openDatesFile, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not open data file']);
        exit;
    }

    // Acquire an exclusive lock (will block until available)
    if (flock($fp, LOCK_EX)) {
        // Read current content
        $filesize = filesize($openDatesFile);
        $content = $filesize > 0 ? fread($fp, $filesize) : '{}';
        $openDates = json_decode($content, true);
        
        if ($openDates === null && json_last_error() !== JSON_ERROR_NONE) {
            $openDates = []; // Fallback if corrupted
        }

        // Update data
        if ($openDate === '') {
            unset($openDates[$conid]);
        } else {
            $openDates[$conid] = $openDate;
        }

        // Write back
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($openDates, JSON_PRETTY_PRINT));
        fflush($fp);            // flush output before releasing the lock
        flock($fp, LOCK_UN);    // release the lock
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not lock data file']);
        fclose($fp);
        exit;
    }
    fclose($fp);

    echo json_encode(['success' => true, 'open_dates' => $openDates]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
