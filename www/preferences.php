<?php
declare(strict_types=1);

/**
 * Manage user preferences in preferences.json with file locking to prevent race conditions.
 */

header('Content-Type: application/json; charset=utf-8');

$prefsFile = __DIR__ . '/preferences.json';

// Initialize file if not exists
if (!file_exists($prefsFile)) {
    file_put_contents($prefsFile, json_encode([], JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo file_get_contents($prefsFile);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }

    // Use file locking for atomic updates
    $fp = fopen($prefsFile, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not open data file']);
        exit;
    }

    if (flock($fp, LOCK_EX)) {
        $filesize = filesize($prefsFile);
        $content = $filesize > 0 ? fread($fp, $filesize) : '{}';
        $prefs = json_decode($content, true);
        
        if ($prefs === null && json_last_error() !== JSON_ERROR_NONE) {
            $prefs = [];
        }

        // Merge new preferences into existing ones
        foreach ($input as $key => $value) {
            $prefs[$key] = $value;
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($prefs, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not lock data file']);
        fclose($fp);
        exit;
    }
    fclose($fp);

    echo json_encode(['success' => true, 'preferences' => $prefs]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
